<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(Request $r): View
    {
        $q = Scan::withCount('detections');
        if ($r->user()->isConsumer()) {
            $q->where('user_id', $r->user()->id);
        }

return view('scans.index', ['items' => $q->latest()->paginate(10)]);
    }

    public function create(): View
    {
        return view('scans.create', ['item' => new Scan]);
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $this->validated($r);
        $data['user_id'] = $r->user()->id;
        $data['scanned_at'] = $data['scanned_at'] ?? now();
        if ($r->user()->isConsumer()) {
            $data['status'] = 'pending';
            $data['confidence'] = null;
        }Scan::create($data);

        return to_route('scans.index')->with('success', 'Scan enregistré.');
    }

    public function edit(Request $r, Scan $scan): View
    {
        $this->guard($r, $scan);

        return view('scans.edit', ['item' => $scan]);
    }

    public function update(Request $r, Scan $scan): RedirectResponse
    {
        $this->guard($r, $scan);
        $data = $this->validated($r);
        if ($r->user()->isConsumer()) {
            $data['status'] = $scan->status;
            $data['confidence'] = $scan->confidence;
        }$scan->update($data);

        return to_route('scans.index')->with('success', 'Scan mis à jour.');
    }

    public function destroy(Request $r, Scan $scan): RedirectResponse
    {
        $this->guard($r, $scan);
        $scan->delete();

        return back()->with('success', 'Scan supprimé.');
    }

    private function validated(Request $r): array
    {
        return $r->validate(['source_type' => 'required|in:ticket,label,barcode,qr', 'image_path' => 'nullable|string|max:255', 'raw_text' => 'nullable|string', 'status' => 'required|in:pending,processing,completed,failed', 'confidence' => 'nullable|numeric|min:0|max:100', 'scanned_at' => 'nullable|date']);
    }

    private function guard(Request $r, Scan $scan): void
    {
        abort_if($r->user()->isConsumer() && $scan->user_id !== $r->user()->id, 403);
    }
}
