<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\MaterialOriginCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Côté marque, les lots sont en lecture seule : ils sont créés uniquement par le fournisseur
 * quand il expédie une commande, avec son certificat. La marque ne peut pas fabriquer une preuve
 * à sa place ; elle consulte les documents, confirme la réception et relie les lots à ses produits.
 */
class BrandSupplierBatchController extends Controller
{
    public function document(Request $request, MaterialOriginCertificate $certificate): StreamedResponse
    {
        abort_unless($certificate->batch->supplier->brand_id === $request->user()->brand?->id, 403);
        abort_unless($certificate->document_path && Storage::disk('local')->exists($certificate->document_path), 404);

        return Storage::disk('local')->response($certificate->document_path);
    }
}
