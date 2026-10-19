<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Services\NotificationCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationCenterController extends Controller
{
    public function open(Request $request, int $notification): RedirectResponse
    {
        $item = NotificationCenter::queryFor($request->user())->findOrFail($notification);
        $item->update(['read_at' => $item->read_at ?? now()]);

        return $item->url ? redirect($item->url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        NotificationCenter::queryFor($request->user())->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
