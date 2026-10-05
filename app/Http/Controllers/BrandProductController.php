<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandProductController extends Controller
{
    public function index(Request $request): View
    {
        return view('brand.products.index', ['products' => $request->user()->brand->products()->with('materials')->latest()->paginate(12)]);
    }

    public function create(): View
    {
        return view('brand.products.form', ['product' => new Product, 'materials' => Material::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['brand_id'] = $request->user()->brand->id;
        $data['brand'] = $request->user()->brand->name;
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['status'] = $data['is_active'] ? 'published' : 'draft';
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('products', 'public');
        $product = Product::create($data);
        $this->syncMaterials($request, $product);
        return to_route('brand.products.index')->with('success', 'Produit créé.');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->own($request, $product);
        return view('brand.products.form', ['product' => $product->load('materials'), 'materials' => Material::orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->own($request, $product);
        $data = $this->validated($request, $product);
        $data['status'] = $data['is_active'] ? 'published' : 'draft';
        if ($request->hasFile('image')) {
            if ($product->image_path) Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        $product->update($data);
        $this->syncMaterials($request, $product);
        return to_route('brand.products.index')->with('success', 'Produit mis à jour.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->own($request, $product);
        $product->delete();
        return back()->with('success', 'Produit supprimé.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $id = $product?->id;
        $data = $request->validate([
            'name'=>'required|string|max:255','sku'=>"required|string|max:100|unique:products,sku,$id",
            'barcode'=>"nullable|string|max:100|unique:products,barcode,$id",'qr_code'=>"nullable|string|max:255|unique:products,qr_code,$id",
            'category'=>'required|string|max:100','description'=>'nullable|string','origin'=>'nullable|string|max:150',
            'image'=>'nullable|image|max:4096','recyclability_score'=>'nullable|integer|min:0|max:100',
            'recycling_instructions'=>'nullable|string','is_active'=>'nullable|boolean',
            'repairable'=>'nullable|boolean','reusable'=>'nullable|boolean','recyclable'=>'nullable|boolean',
            'materials'=>'nullable|array','materials.*'=>'nullable|integer|min:0|max:100',
        ]);
        foreach (['is_active','repairable','reusable','recyclable'] as $field) $data[$field] = $request->boolean($field);
        unset($data['image'], $data['materials']);
        return $data;
    }

    private function syncMaterials(Request $request, Product $product): void
    {
        $sync = [];
        foreach ($request->input('materials', []) as $materialId => $percentage) {
            if ((int) $percentage > 0) $sync[$materialId] = ['percentage'=>(int)$percentage,'origin_country'=>$product->origin,'certified'=>false];
        }
        $product->materials()->sync($sync);
    }

    private function own(Request $request, Product $product): void
    {
        abort_unless($product->brand_id === $request->user()->brand->id, 403);
    }
}
