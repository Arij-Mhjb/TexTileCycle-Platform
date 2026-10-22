<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminCollectorController extends Controller
{
    public function index(): View
    {
        return view('admin.collectors', [
            'collectors' => User::where('role', 'collector')->withCount('operatedCollectionPoints')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $collector = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'collector',
        ]);
        $collector->forceFill(['email_verified_at' => now()])->save();

        return back()->with('success', 'Compte collecteur créé. Vous pouvez maintenant l’affecter à un point.');
    }

    public function destroy(User $collector): RedirectResponse
    {
        abort_unless($collector->isCollector(), 404);
        if ($collector->operatedCollectionPoints()->exists()) {
            return back()->withErrors(['collector' => 'Réaffectez d’abord les points de collecte de ce compte.']);
        }
        $collector->delete();

        return back()->with('success', 'Compte collecteur supprimé.');
    }
}
