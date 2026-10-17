<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MaterialOriginCertificate;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Services\Ai\SupplierAiService;
use App\Services\SupplierTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandSupplierBatchController extends Controller
{
    public function store(Request $request, Supplier $supplier, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($supplier->brand_id === $request->user()->brand?->id, 403);
        $data = $request->validate([
            'batch_code' => 'required|string|max:80|unique:supplier_batches,batch_code',
            'material_id' => 'required|exists:materials,id',
            'purchase_order_id' => ['nullable', Rule::exists('purchase_orders', 'id')->where('supplier_id', $supplier->id)],
            'quantity_kg' => 'required|numeric|min:0.1|max:9999999',
            'origin_country' => 'required|string|max:100',
            'production_date' => 'nullable|date|before_or_equal:today',
            'received_at' => 'nullable|date',
        ]);

        $supplier->batches()->create([...$data, 'status' => ! empty($data['received_at']) ? 'received' : 'pending']);
        $traceability->refreshComplianceScore($supplier);

        return back()->with('success', 'Lot '.$data['batch_code'].' enregistré. Il sera vérifié par TexTileCycle.');
    }

    public function destroy(Request $request, SupplierBatch $batch, SupplierTraceabilityService $traceability): RedirectResponse
    {
        abort_unless($batch->supplier->brand_id === $request->user()->brand?->id, 403);
        abort_if($batch->status === 'verified', 422, 'Un lot vérifié ne peut plus être supprimé.');
        $supplier = $batch->supplier;
        $batch->delete();
        $traceability->refreshComplianceScore($supplier);

        return back()->with('success', 'Lot supprimé.');
    }

    /**
     * La marque envoie seulement la photo du certificat : l'IA lit le type, le numéro,
     * l'organisme et les dates, et le certificat est enregistré (en attente de vérification admin).
     */
    public function certificate(Request $request, SupplierBatch $batch, SupplierTraceabilityService $traceability, SupplierAiService $ai): RedirectResponse
    {
        abort_unless($batch->supplier->brand_id === $request->user()->brand?->id, 403);
        $request->validate(
            ['document' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192'],
            ['document.required' => 'Ajoutez la photo ou le scan du certificat.'],
            ['document' => 'document du certificat']
        );

        $file = $request->file('document');
        $result = $ai->extractCertificate($file->getRealPath(), $batch);
        if (($result['is_certificate'] ?? true) === false) {
            return back()->withErrors(['document' => 'L’IA ne reconnaît pas un certificat dans cette image ('.$result['document_kind'].'). Envoyez la photo du certificat GRS, GOTS, RWS, OEKO-TEX…']);
        }
        $fields = $result['fields'];
        $path = $file->store('certificates/'.$batch->id, 'local');

        $batch->certificates()->create([
            'type' => array_key_exists((string) ($fields['type'] ?? ''), MaterialOriginCertificate::TYPES) ? $fields['type'] : null,
            'certificate_number' => $this->text($fields['certificate_number'] ?? null),
            'issuer' => $this->text($fields['issuer'] ?? null),
            'issued_at' => $this->date($fields['issued_at'] ?? null),
            'expires_at' => $this->date($fields['expires_at'] ?? null),
            'document_path' => $path,
            'ai_extraction' => $result,
            'status' => 'pending',
        ]);
        $traceability->refreshComplianceScore($batch->supplier);

        $read = collect(['type', 'certificate_number', 'issuer', 'issued_at', 'expires_at'])->filter(fn ($key) => filled($fields[$key] ?? null))->count();

        return back()->with('success', $read > 0
            ? 'Certificat ajouté : l’IA a lu '.$read.' information(s) sur 5. TexTileCycle va le vérifier.'
            : 'Certificat ajouté. L’IA n’a pas pu le lire : TexTileCycle le vérifiera directement sur le document.');
    }

    public function document(Request $request, MaterialOriginCertificate $certificate): StreamedResponse
    {
        abort_unless($certificate->batch->supplier->brand_id === $request->user()->brand?->id, 403);
        abort_unless($certificate->document_path && Storage::disk('local')->exists($certificate->document_path), 404);

        return Storage::disk('local')->response($certificate->document_path);
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, 150) : null;
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || ! strtotime($value)) {
            return null;
        }

        return $value;
    }
}
