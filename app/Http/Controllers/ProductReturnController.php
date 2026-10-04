<?php

namespace App\Http\Controllers;

use App\Models\CollectionPoint;
use App\Models\Product;
use App\Models\ProductReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReturnController extends Controller
{
    public function index(Request $r): View
    {
        $q = ProductReturn::with(['product', 'collectionPoint']);
        if ($r->user()->isConsumer()) {
            $q->where('user_id', $r->user()->id);
        }

return view('product-returns.index', ['items' => $q->latest()->paginate(10)]);
    }

    public function create(): View
    {
        return view('product-returns.create', ['item' => new ProductReturn, 'products' => Product::where('status', 'published')->orderBy('name')->get(), 'points' => CollectionPoint::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $this->validated($r);
        $data['user_id'] = $r->user()->id;
        if ($r->user()->isConsumer()) {
            $data['status'] = 'declared';
        }ProductReturn::create($data);

        return to_route('product-returns.index')->with('success', 'Retour enregistré.');
    }

    public function edit(Request $r, ProductReturn $productReturn): View
    {
        $this->guard($r, $productReturn);

        return view('product-returns.edit', ['item' => $productReturn, 'products' => Product::where('status', 'published')->orderBy('name')->get(), 'points' => CollectionPoint::where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(Request $r, ProductReturn $productReturn): RedirectResponse
    {
        $this->guard($r, $productReturn);
        $data = $this->validated($r, $productReturn);
        if ($r->user()->isConsumer()) {
            $data['status'] = $productReturn->status;
        }$productReturn->update($data);

        return to_route('product-returns.index')->with('success', 'Retour mis à jour.');
    }

    public function destroy(Request $r, ProductReturn $productReturn): RedirectResponse
    {
        $this->guard($r, $productReturn);
        $productReturn->delete();

        return back()->with('success', 'Retour supprimé.');
    }

    private function validated(Request $r, ?ProductReturn $x = null): array
    {
        return $r->validate(['product_id' => 'required|exists:products,id', 'collection_point_id' => 'required|exists:collection_points,id', 'reference' => 'required|string|max:80|unique:product_returns,reference,'.($x?->id ?? 'NULL'), 'status' => 'required|in:declared,received,sorting,processed,rejected', 'weight_kg' => 'nullable|numeric|min:0|max:999999', 'returned_at' => 'nullable|date', 'notes' => 'nullable|string']);
    }

    private function guard(Request $r, ProductReturn $return): void
    {
        abort_if($r->user()->isConsumer() && $return->user_id !== $r->user()->id, 403);
    }
}
