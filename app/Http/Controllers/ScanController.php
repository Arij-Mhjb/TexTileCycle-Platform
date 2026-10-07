<?php

namespace App\Http\Controllers;

use App\Models\CollectionPoint;
use App\Models\Detection;
use App\Models\Product;
use App\Models\Scan;
use App\Services\CircularRecommendationService;
use App\Services\MediaStorageService;
use App\Services\MultimodalScanService;
use App\Services\SmartScanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ScanController extends Controller
{
    public function index(Request $request): View
    {
        $query = Scan::with('product')->withCount(['detections', 'assets']);
        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        return view('scans.index', ['items' => $query->latest('scanned_at')->paginate(12)]);
    }

    public function create(): View
    {
        return view('scans.create', ['item' => new Scan]);
    }

    public function store(
        Request $request,
        SmartScanService $scanner,
        MultimodalScanService $multimodal,
        MediaStorageService $media
    ): RedirectResponse {
        $data = $request->validate([
            'source_type' => 'required|in:barcode,qr,manual,image,multimodal',
            'value' => 'nullable|required_unless:source_type,image,multimodal|string|max:500',
            'image' => 'nullable|required_if:source_type,image|image|mimes:jpg,jpeg,png,webp|max:5120',
            'receipt_image' => 'nullable|required_if:source_type,multimodal|image|mimes:jpg,jpeg,png,webp|max:8192',
            'label_image' => 'nullable|required_if:source_type,multimodal|image|mimes:jpg,jpeg,png,webp|max:8192',
            'product_image' => 'nullable|required_if:source_type,multimodal|image|mimes:jpg,jpeg,png,webp|max:8192',
            'product_back_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'damage_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'privacy_consent' => 'exclude_unless:source_type,multimodal|required|accepted',
        ]);

        $value = $data['value'] ?? '';
        $isMultimodal = $data['source_type'] === 'multimodal';
        $matches = $isMultimodal ? collect() : $scanner->matches($value);
        $product = $matches->count() === 1 ? $matches->first() : null;
        $assetFields = [
            'receipt_image' => 'receipt',
            'label_image' => 'composition_label',
            'product_image' => 'product_front',
            'product_back_image' => 'product_back',
            'damage_image' => 'damage',
        ];

        $uploadedMedia = [];
        DB::beginTransaction();
        try {
            $scan = Scan::create([
                'user_id' => $request->user()->id,
                'product_id' => $product?->id,
                'source_type' => $data['source_type'],
                'raw_text' => $value,
                'status' => $isMultimodal ? 'analysing' : ($product ? 'completed' : ($matches->count() > 1 ? 'multiple_matches' : 'not_found')),
                'ai_status' => $isMultimodal ? 'analysing' : 'not_requested',
                'confidence' => $product ? $scanner->confidence($product, $value) : null,
                'result_summary' => $matches->count() > 1 ? $matches->pluck('name')->join(', ') : null,
                'scanned_at' => now(),
            ]);

            if ($request->hasFile('image')) {
                $stored = $media->storeScanImage($request->file('image'), $scan->user_id, $scan->uuid, 'product_front');
                $uploadedMedia[] = $stored;
                $scan->update($this->scanImageColumns($stored));
            }

            foreach ($assetFields as $field => $type) {
                if ($request->hasFile($field)) {
                    $stored = $media->storeScanImage($request->file($field), $scan->user_id, $scan->uuid, $type);
                    $uploadedMedia[] = $stored;
                    $scan->assets()->create(['type' => $type, ...$stored]);
                }
            }

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            foreach ($uploadedMedia as $stored) {
                $media->delete($stored['path'], $stored['storage_disk'], $stored['cloudinary_public_id'], $stored['delivery_type']);
            }
            report($exception);

            return back()->withInput()->withErrors([
                'images' => 'Le stockage sécurisé des images a échoué. Aucun scan incomplet n’a été conservé. Veuillez réessayer.',
            ]);
        }

        if ($isMultimodal) {
            $analysis = $multimodal->analyse($scan);
            $scan->refresh();
            if ($scan->product_id) {
                Detection::updateOrCreate(
                    ['scan_id' => $scan->id, 'product_id' => $scan->product_id],
                    [
                        'detected_name' => $analysis['product_name'] ?? $scan->product->name,
                        'detected_brand' => $analysis['brand'] ?? $scan->product->brand,
                        'detected_category' => $analysis['category'] ?? $scan->product->category,
                        'detected_composition' => json_encode($analysis['composition'] ?? [], JSON_UNESCAPED_UNICODE),
                        'confidence' => $analysis['confidence'] ?? $scan->confidence,
                        'is_confirmed' => false,
                    ]
                );
            }
        } else {
            foreach ($matches as $match) {
                Detection::create([
                    'scan_id' => $scan->id,
                    'product_id' => $match->id,
                    'detected_name' => $match->name,
                    'detected_brand' => $match->brand,
                    'detected_category' => $match->category,
                    'confidence' => $scanner->confidence($match, $value),
                    'is_confirmed' => $product?->is($match) ?? false,
                ]);
            }
        }

        return to_route('scans.show', $scan);
    }

    public function show(Request $request, Scan $scan, CircularRecommendationService $recommendations): View
    {
        $this->guard($request, $scan);
        $scan->load('product.materials', 'detections.product', 'assets');

        $condition = data_get($scan->extracted_data, 'condition');
        $recommendation = $scan->product ? $recommendations->recommend($scan->product, $condition) : null;
        $points = $recommendation
            ? CollectionPoint::where('is_active', true)
                ->whereHas('recoveryProgram', fn ($query) => $query
                    ->where('is_active', true)
                    ->where('type', $recommendation['action']))
                ->with('recoveryProgram')
                ->limit(4)
                ->get()
            : collect();

        $catalogueProducts = Product::where('status', 'published')->where('is_active', true)->orderBy('name')->get();

        return view('scans.show', compact('scan', 'recommendation', 'points', 'catalogueProducts'));
    }

    public function reanalyse(
        Request $request,
        Scan $scan,
        MultimodalScanService $multimodal,
        MediaStorageService $media
    ): RedirectResponse {
        $this->guard($request, $scan);
        abort_unless($scan->source_type === 'multimodal', 422);
        abort_if($scan->consumer_confirmed_at, 422, 'Les données déjà confirmées ne peuvent plus être remplacées.');

        $scan->load('assets');
        $hasAvailableImage = $scan->assets->contains(fn ($asset) => $media->exists(
            $asset->path,
            $asset->storage_disk,
            $asset->cloudinary_public_id,
            $asset->delivery_type,
        ));
        if (! $hasAvailableImage) {
            return back()->withErrors([
                'images' => 'Image originale indisponible après l’ancien déploiement. La réanalyse ne peut pas être lancée.',
            ]);
        }

        $scan->update([
            'status' => 'analysing',
            'ai_status' => 'analysing',
        ]);

        $multimodal->analyse($scan);

        return to_route('scans.show', $scan)->with(
            'success',
            'Nouvelle analyse terminée avec amélioration d’image et lecture du code-barres.'
        );
    }

    public function confirmData(Request $request, Scan $scan): RedirectResponse
    {
        $this->guard($request, $scan);

        $data = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'brand' => 'nullable|string|max:255',
            'product_name' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:100',
            'retailer' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0|max:9999999.99',
            'currency' => 'nullable|string|size:3',
            'condition' => 'required|in:good,wearable,minor_damage,damaged,unusable,unknown',
            'composition_text' => 'nullable|string|max:3000',
            'defects_text' => 'nullable|string|max:2000',
        ]);

        $composition = collect(preg_split('/\r\n|\r|\n/', $data['composition_text'] ?? ''))
            ->filter()
            ->map(function ($line) {
                [$material, $percentage] = array_pad(explode(':', $line, 2), 2, null);

                return ['material' => trim($material), 'percentage' => $percentage !== null ? (float) trim($percentage) : null];
            })
            ->values()
            ->all();

        $extracted = [
            ...($scan->extracted_data ?? []),
            ...collect($data)->except(['product_id', 'composition_text', 'defects_text'])->all(),
            'composition' => $composition,
            'defects' => collect(preg_split('/\r\n|\r|\n/', $data['defects_text'] ?? ''))->filter()->values()->all(),
        ];

        $scan->update([
            'product_id' => $data['product_id'] ?? $scan->product_id,
            'extracted_data' => $extracted,
            'consumer_confirmed_at' => now(),
            'status' => 'consumer_confirmed',
        ]);

        return to_route('scans.show', $scan)->with('success', 'Informations confirmées. Vous pouvez préparer le dépôt.');
    }

    public function confirm(Request $request, Scan $scan, Product $product): RedirectResponse
    {
        $this->guard($request, $scan);
        $detection = $scan->detections()->where('product_id', $product->id)->firstOrFail();

        $scan->detections()->update(['is_confirmed' => false]);
        $detection->update(['is_confirmed' => true]);
        $scan->update([
            'product_id' => $product->id,
            'status' => 'completed',
            'confidence' => $detection->confidence,
        ]);

        return to_route('scans.show', $scan)->with('success', 'Produit confirmé.');
    }

    public function destroy(Request $request, Scan $scan, MediaStorageService $media): RedirectResponse
    {
        $this->guard($request, $scan);
        $scan->load('assets');
        $references = $scan->assets->map(fn ($asset) => [
            $asset->path, $asset->storage_disk, $asset->cloudinary_public_id, $asset->delivery_type,
        ])->all();
        if ($scan->image_path) {
            $references[] = [
                $scan->image_path, $scan->image_storage_disk,
                $scan->image_cloudinary_public_id, $scan->image_delivery_type,
            ];
        }
        $scan->delete();

        foreach ($references as [$path, $disk, $publicId, $deliveryType]) {
            $media->delete($path, $disk, $publicId, $deliveryType);
        }

        return to_route('scans.index')->with('success', 'Scan supprimé.');
    }

    private function guard(Request $request, Scan $scan): void
    {
        abort_unless($request->user()->isAdmin() || $scan->user_id === $request->user()->id, 403);
    }

    private function scanImageColumns(array $stored): array
    {
        return [
            'image_path' => $stored['path'],
            'image_storage_disk' => $stored['storage_disk'],
            'image_cloudinary_public_id' => $stored['cloudinary_public_id'],
            'image_secure_url' => $stored['secure_url'],
            'image_delivery_type' => $stored['delivery_type'],
            'image_format' => $stored['format'],
            'image_version' => $stored['version'],
        ];
    }
}
