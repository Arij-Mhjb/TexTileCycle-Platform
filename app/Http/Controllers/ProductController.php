<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $materialIds = $data['material_ids'] ?? [];
        unset($data['material_ids'], $data['image'], $data['remove_image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);
        $product->materials()->sync($materialIds);

        return to_route('products.index')->with('success', 'Produit créé avec succès.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'item' => $product->load('materials'),
            'materials' => Material::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $materialIds = $data['material_ids'] ?? [];
        unset($data['material_ids'], $data['image'], $data['remove_image']);

        $oldImagePath = $product->image_path;
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        $product->update($data);
        $product->materials()->sync($materialIds);

        if ($oldImagePath && $oldImagePath !== $product->image_path) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return to_route('products.index')->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return back()->with('success', 'Produit supprimé.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'brand' => 'required|string|max:120',
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
}
