<?php

namespace App\Http\Controllers;

use App\Models\MaterialMatch;
use App\Services\MaterialExchangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConsumerMaterialTransferController extends Controller
{
    public function store(Request $request, MaterialMatch $materialMatch, MaterialExchangeService $exchange): RedirectResponse
    {
        $exchange->transfer($materialMatch, $request->user());

        return back()->with('success', 'Transfert confirmé. La marque a été notifiée.');
    }
}
