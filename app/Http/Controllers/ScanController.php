<?php

namespace App\Http\Controllers;

use App\Models\CollectionPoint;
use App\Models\Detection;
use App\Models\Product;
use App\Models\Scan;
use App\Services\CircularRecommendationService;
use App\Services\MultimodalScanService;
use App\Services\SmartScanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    public function store(Request $request, SmartScanService $scanner, MultimodalScanService $multimodal): RedirectResponse
    {
        $data = $request->validate([
            'source_type' => 'required|in:barcode,qr,manual,image,multimodal',
            'value' => 'nullable|required_unless:source_type,image,multimodal|string|max:500',
            'image' => 'nullable|required_if:source_type,image|image|max:5120',
            'receipt_image' => 'nullable|required_if:source_type,multimodal|image|max:8192',
            'label_image' => 'nullable|required_if:source_type,multimodal|image|max:8192',
            'product_image' => 'nullable|required_if:source_type,multimodal|image|max:8192',
            'product_back_image' => 'nullable|image|max:8192',
            'damage_image' => 'nullable|image|max:8192',
        ]);

        $value = $data['value'] ?? '';
        $isMultimodal = $data['source_type'] === 'multimodal';
        $matches = $isMultimodal ? collect() : $scanner->matches($value);
        $product = $matches->count() === 1 ? $matches->first() : null;
        $path = $request->hasFile('image') ? $request->file('image')->store('scans', 'public') : null;

        $scan = Scan::create([
            'user_id' => $request->user()->id,
            'product_id' => $product?->id,
            'source_type' => $data['source_type'],
            'image_path' => $path,
            'raw_text' => $value,
            'status' => $isMultimodal ? 'analysing' : ($product ? 'completed' : ($matches->count() > 1 ? 'multiple_matches' : 'not_found')),
            'ai_status' => $isMultimodal ? 'analysing' : 'not_requested',
            'confidence' => $product ? $scanner->confidence($product, $value) : null,
            'result_summary' => $matches->count() > 1 ? $matches->pluck('name')->join(', ') : null,
            'scanned_at' => now(),
        ]);

        $assetFields = [
            'receipt_image' => 'receipt',
            'label_image' => 'composition_label',
            'product_image' => 'product_front',
            'product_back_image' => 'product_back',
            'damage_image' => 'damage',
        ];

        foreach ($assetFields as $field => $type) {
            if ($request->hasFile($field)) {
                $scan->assets()->create([
                    'type' => $type,
                    'path' => $request->file($field)->store('scans/'.$scan->id, 'public'),
                ]);
            }
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

    public function destroy(Request $request, Scan $scan): RedirectResponse
    {
        $this->guard($request, $scan);
        $scan->delete();

        return to_route('scans.index')->with('success', 'Scan supprimé.');
    }

    private function guard(Request $request, Scan $scan): void
    {
        abort_unless($request->user()->isAdmin() || $scan->user_id === $request->user()->id, 403);
    }
}
