<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumerWardrobeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $products = $request->user()->scans()->whereNotNull('product_id')->with(['product.materials', 'passportProposal'])
            ->latest('scanned_at')->get()->unique('product_id');

        return view('consumer.wardrobe', [
            'scans' => $products,
            'returnsByProduct' => $request->user()->productReturns()->latest()->get()->groupBy('product_id'),
        ]);
    }
}
