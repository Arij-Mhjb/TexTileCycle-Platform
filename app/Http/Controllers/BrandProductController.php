<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Product;
use App\Services\ActivityService;
use App\Services\MediaStorageService;
use App\Services\ProductCompletenessService;
use App\Services\ProductWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BrandProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->brand->products()->with('materials');
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('sku', 'like', '%'.$request->search.'%')->orWhere('barcode', 'like', '%'.$request->search.'%'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('brand.products.index', ['products' => $query->latest()->paginate(12)->withQueryString()]);
    }

    public function create(): View
    {
        return view('brand.products.form', ['product' => new Product, 'materials' => Material::orderBy('name')->get(), 'analysis' => null]);
    }

    public function store(Request $request, MediaStorageService $media): RedirectResponse
    {
        [$data,$materials] = $this->validated($request);
        $data += ['brand_id' => $request->user()->brand->id, 'brand' => $request->user()->brand->name, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)), 'status' => 'draft', 'is_active' => false];
        $stored = null;
        try {
            if ($request->hasFile('image')) {
                $stored = $media->storeProductImage($request->file('image'));
                $data += $this->productImageColumns($stored);
            }
            $product = DB::transaction(function () use ($data, $materials) {
                $product = Product::create($data);
                $this->syncMaterials($product, $materials);

                return $product;
            });
        } catch (\Throwable $exception) {
            if ($stored) {
                $media->delete($stored['path'], $stored['storage_disk'], $stored['cloudinary_public_id'], $stored['delivery_type']);
            }
            report($exception);

            return back()->withInput()->withErrors(['image' => 'Le produit n’a pas pu être enregistré avec sa photo.']);
        }
        $analysis = app(ProductCompletenessService::class)->refresh($product);
        app(ActivityService::class)->log($request->user()->id, $product->brand_id, 'product.created', $product, ['completion' => $analysis['score']]);

        return to_route('brand.products.edit', $product)->with('success', 'Produit créé en brouillon.');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->own($request, $product);
        $product->load('materials', 'versions.author');

        return view('brand.products.form', ['product' => $product, 'materials' => Material::orderBy('name')->get(), 'analysis' => app(ProductCompletenessService::class)->analyse($product)]);
    }

    public function update(Request $request, Product $product, MediaStorageService $media): RedirectResponse
    {
        $this->own($request, $product);
        if ($product->status === 'published') {
            app(ProductWorkflowService::class)->saveVersion($product, $request->user()->id, 'Avant modification');
            $product->increment('current_version');
        }
        [$data,$materials] = $this->validated($request, $product);
        $data['status'] = 'draft';
        $data['is_active'] = false;
        $data['review_comment'] = null;
        $oldImage = [$product->image_path, $product->image_storage_disk, $product->image_cloudinary_public_id, $product->image_delivery_type];
        $stored = null;
        try {
            if ($request->hasFile('image')) {
                $stored = $media->storeProductImage($request->file('image'));
                $data += $this->productImageColumns($stored);
            }
            DB::transaction(function () use ($product, $data, $materials) {
                $product->update($data);
                $this->syncMaterials($product, $materials);
            });
        } catch (\Throwable $exception) {
            if ($stored) {
                $media->delete($stored['path'], $stored['storage_disk'], $stored['cloudinary_public_id'], $stored['delivery_type']);
            }
            report($exception);

            return back()->withInput()->withErrors(['image' => 'La mise à jour du produit et de sa photo a échoué.']);
        }
        if ($stored && $oldImage[0]) {
            $media->delete(...$oldImage);
        }
        $analysis = app(ProductCompletenessService::class)->refresh($product);
        app(ActivityService::class)->log($request->user()->id, $product->brand_id, 'product.updated', $product, ['completion' => $analysis['score']]);

        return back()->with('success', 'Brouillon mis à jour.');
    }

    public function submit(Request $request, Product $product): RedirectResponse
    {
        $this->own($request, $product);
        app(ProductWorkflowService::class)->submit($product, $request->user()->id);
        app(ActivityService::class)->log($request->user()->id, $product->brand_id, 'product.submitted', $product);

        return back()->with('success', 'Produit soumis à la vérification.');
    }

    public function duplicate(Request $request, Product $product): RedirectResponse
    {
        $this->own($request, $product);
        $copy = $product->replicate(['slug', 'sku', 'barcode', 'qr_code', 'status', 'completion_score', 'submitted_at', 'verified_at', 'published_at', 'archived_at']);
        $copy->name = $product->name.' — copie';
        $copy->slug = Str::slug($copy->name).'-'.Str::lower(Str::random(5));
        $copy->sku = $product->sku.'-COPY-'.Str::upper(Str::random(4));
        $copy->status = 'draft';
        $copy->is_active = false;
        $copy->save();
        $copy->materials()->sync($product->materials->mapWithKeys(fn ($m) => [$m->id => ['percentage' => $m->pivot->percentage, 'origin_country' => $m->pivot->origin_country, 'certified' => $m->pivot->certified, 'component' => $m->pivot->component, 'supplier' => $m->pivot->supplier, 'sourcing_type' => $m->pivot->sourcing_type]])->all());
        app(ProductCompletenessService::class)->refresh($copy);

        return to_route('brand.products.edit', $copy)->with('success', 'Produit dupliqué.');
    }

    public function archive(Request $request, Product $product): RedirectResponse
    {
        $this->own($request, $product);
        app(ProductWorkflowService::class)->archive($product);
        app(ActivityService::class)->log($request->user()->id, $product->brand_id, 'product.archived', $product);

        return back()->with('success', 'Produit archivé.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->own($request, $product);
        $product->delete();

        return back()->with('success', 'Produit placé dans la corbeille.');
    }

    public function trash(Request $request): View
    {
        return view('brand.products.trash', ['products' => $request->user()->brand->products()->onlyTrashed()->latest('deleted_at')->paginate(20)]);
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $product = $request->user()->brand->products()->onlyTrashed()->findOrFail($id);
        $product->restore();
        app(ActivityService::class)->log($request->user()->id, $product->brand_id, 'product.restored', $product);

        return to_route('brand.products.index')->with('success', 'Produit restauré en brouillon.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $id = $product?->id;
        $data = $request->validate([
            'name' => 'required|string|max:255', 'sku' => "required|string|max:100|unique:products,sku,$id",
            'barcode' => "nullable|string|max:100|unique:products,barcode,$id", 'qr_code' => "nullable|string|max:255|unique:products,qr_code,$id",
            'category' => 'required|string|max:100', 'description' => 'nullable|string', 'origin' => 'nullable|string|max:150',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096', 'recyclability_score' => 'nullable|integer|min:0|max:100', 'recycling_instructions' => 'nullable|string',
            'repairable' => 'nullable|boolean', 'reusable' => 'nullable|boolean', 'recyclable' => 'nullable|boolean',
            'materials' => 'nullable|array', 'materials.*' => 'nullable|numeric|min:0|max:100|decimal:0,2',
        ]);
        $materials = array_filter($request->input('materials', []), fn ($v) => (float) $v > 0);
        $total = round(array_sum($materials), 2);
        if ($materials && abs($total - 100) > 0.01) {
            throw ValidationException::withMessages(['materials' => "La composition doit totaliser exactement 100 %. Total actuel : {$total} %."]);
        }
        foreach (['repairable', 'reusable', 'recyclable'] as $field) {
            $data[$field] = $request->boolean($field);
        }
        unset($data['image'],$data['materials']);

        return [$data, $materials];
    }

    private function syncMaterials(Product $product, array $materials): void
    {
        $sync = [];
        foreach ($materials as $id => $percentage) {
            $sync[$id] = ['percentage' => $percentage, 'origin_country' => $product->origin, 'certified' => false, 'component' => 'main'];
        }
        $product->materials()->sync($sync);
    }

    private function own(Request $request, Product $product): void
    {
        abort_unless($product->brand_id === $request->user()->brand->id, 403);
    }

    private function productImageColumns(array $stored): array
    {
        return ['image_path' => $stored['path'], 'image_storage_disk' => $stored['storage_disk'], 'image_cloudinary_public_id' => $stored['cloudinary_public_id'], 'image_secure_url' => $stored['secure_url'], 'image_delivery_type' => $stored['delivery_type'], 'image_format' => $stored['format'], 'image_version' => $stored['version']];
    }
}
