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
        $query = Product::where('status', 'published')->with('materials');
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%"));
        }

        return view('consumer.products', ['products' => $query->latest()->paginate(9)->withQueryString()]);
    }

    public function programs(Request $request): View
    {
        $query = RecoveryProgram::where('is_active', true)->withCount('collectionPoints');
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return view('consumer.programs', ['programs' => $query->latest()->paginate(9)->withQueryString()]);
    }

    public function points(Request $request): View
    {
        $query = CollectionPoint::where('is_active', true)->with('recoveryProgram');
        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->string('city').'%');
        }

        return view('consumer.points', ['points' => $query->latest()->paginate(9)->withQueryString()]);
    }
}
