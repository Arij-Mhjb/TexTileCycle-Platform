<?php

namespace App\Http\Controllers;

use App\Models\CollectionPoint;
use App\Models\Product;
use App\Models\RecoveryProgram;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumerExploreController extends Controller
{
    public function products(Request $request): View
    {
        $query = Product::query()->where('status', 'published')->where('is_active', true)->with(['materials','brandProfile']);
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('name','like',"%{$search}%")->orWhere('brand','like',"%{$search}%")->orWhere('category','like',"%{$search}%"));
        }
        if ($request->filled('category')) $query->where('category', $request->string('category'));
        if ($request->filled('origin')) $query->where('origin', 'like', '%'.$request->string('origin').'%');
        if ($request->boolean('recyclable')) $query->where('recyclable', true);
        if ($request->boolean('reusable')) $query->where('reusable', true);
        if ($request->boolean('repairable')) $query->where('repairable', true);
        if ($request->filled('score')) $query->where('recyclability_score', '>=', (int) $request->input('score'));

        return view('consumer.products', [
            'products'=>$query->latest()->paginate(9)->withQueryString(),
            'categories'=>Product::where('is_active',true)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_active && $product->status === 'published', 404);
        return view('consumer.product-show', ['product'=>$product->load(['materials','brandProfile'])]);
    }

    public function programs(Request $request): View
    {
        $query=RecoveryProgram::where('is_active',true)->withCount('collectionPoints');
        if($request->filled('type')) $query->where('type',$request->string('type'));
        return view('consumer.programs',['programs'=>$query->latest()->paginate(9)->withQueryString()]);
    }

    public function points(Request $request): View
    {
        $query=CollectionPoint::where('is_active',true)->with('recoveryProgram');
        if($request->filled('city')) $query->where('city','like','%'.$request->string('city').'%');
        return view('consumer.points',['points'=>$query->latest()->paginate(9)->withQueryString()]);
    }
}
