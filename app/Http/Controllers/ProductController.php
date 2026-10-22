<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Material;
use App\Models\Product;
use App\Services\MediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::withCount('materials');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        return view('products.index', [
            'items' => $query->latest()->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'item' => new Product,
            'materials' => Material::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, MediaStorageService $media): RedirectResponse
    {
        $data = $this->validated($request);
        $brand = Brand::findOrFail($data['brand_id']);
        $data['brand'] = $brand->name;
        $materialIds = $data['material_ids'] ?? [];
        unset($data['material_ids'], $data['image'], $data['remove_image']);

        $stored = null;
        try {
            if ($request->hasFile('image')) {
                $stored = $media->storeProductImage($request->file('image'));
                $data += $this->productImageColumns($stored);
            }

            DB::transaction(function () use ($data, $materialIds) {
                $product = Product::create($data);
                $product->materials()->sync($materialIds);
            });
        } catch (\Throwable $exception) {
            if ($stored) {
                $media->delete($stored['path'], $stored['storage_disk'], $stored['cloudinary_public_id'], $stored['delivery_type']);
            }
            report($exception);

            return back()->withInput()->withErrors(['image' => 'Le produit n’a pas pu être enregistré avec sa photo.']);
        }

        return to_route('products.index')->with('success', 'Produit créé avec succès.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'item' => $product->load('materials'),
            'materials' => Material::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product, MediaStorageService $media): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $brand = Brand::findOrFail($data['brand_id']);
        $data['brand'] = $brand->name;
        $materialIds = $data['material_ids'] ?? [];
        unset($data['material_ids'], $data['image'], $data['remove_image']);

        $oldImage = $this->productImageReference($product);
        $stored = null;
        try {
            if ($request->hasFile('image')) {
                $stored = $media->storeProductImage($request->file('image'));
                $data += $this->productImageColumns($stored);
            } elseif ($request->boolean('remove_image')) {
                $data += $this->productImageColumns(null);
            }

            DB::transaction(function () use ($product, $data, $materialIds) {
                $product->update($data);
                $product->materials()->sync($materialIds);
            });
        } catch (\Throwable $exception) {
            if ($stored) {
                $media->delete($stored['path'], $stored['storage_disk'], $stored['cloudinary_public_id'], $stored['delivery_type']);
            }
            report($exception);

            return back()->withInput()->withErrors(['image' => 'La mise à jour du produit et de sa photo a échoué.']);
        }

        if (($stored || $request->boolean('remove_image')) && $oldImage[0]) {
            $media->delete(...$oldImage);
        }

        return to_route('products.index')->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product, MediaStorageService $media): RedirectResponse
    {
        $image = $this->productImageReference($product);
        $product->delete();
        if ($image[0]) {
            $media->delete(...$image);
        }

        return back()->with('success', 'Produit supprimé.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'brand_id' => 'required|integer|exists:brands,id',
            'sku' => 'required|string|max:80|unique:products,sku,'.($product?->id ?? 'NULL'),
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_image' => 'nullable|boolean',
            'status' => 'required|in:draft,published,archived',
            'year' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'repairable' => 'boolean',
            'reusable' => 'boolean',
            'recyclable' => 'boolean',
            'environmental_score' => 'nullable|integer|min:0|max:100',
            'circularity_score' => 'nullable|integer|min:0|max:100',
            'animal_free_score' => 'nullable|integer|min:0|max:100',
            'traceability_score' => 'nullable|integer|min:0|max:100',
            'material_ids' => 'nullable|array',
            'material_ids.*' => 'exists:materials,id',
        ]);
    }

    private function productImageColumns(?array $stored): array
    {
        return [
            'image_path' => $stored['path'] ?? null,
            'image_storage_disk' => $stored['storage_disk'] ?? null,
            'image_cloudinary_public_id' => $stored['cloudinary_public_id'] ?? null,
            'image_secure_url' => $stored['secure_url'] ?? null,
            'image_delivery_type' => $stored['delivery_type'] ?? null,
            'image_format' => $stored['format'] ?? null,
            'image_version' => $stored['version'] ?? null,
        ];
    }

    private function productImageReference(Product $product): array
    {
        return [
            $product->image_path,
            $product->image_storage_disk,
            $product->image_cloudinary_public_id,
            $product->image_delivery_type,
        ];
    }
}
