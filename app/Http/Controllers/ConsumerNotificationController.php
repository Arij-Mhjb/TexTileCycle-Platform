<?php

namespace App\Http\Controllers;

use App\Models\ConsumerNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumerNotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('consumer.notifications', [
            'notifications' => $request->user()->consumerNotifications()->latest()->paginate(20),
        ]);
    }

    public function read(Request $request, ConsumerNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return $notification->url ? redirect($notification->url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->consumerNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }
}
