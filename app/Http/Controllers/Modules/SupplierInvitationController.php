<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\SupplierInvitation;
use App\Services\SupplierAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Page publique d'activation : le fournisseur ouvre le lien reçu et choisit son mot de passe. */
class SupplierInvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = SupplierInvitation::findByToken($token);

        return view('modules.suppliers.invitation', ['invitation' => $invitation, 'token' => $token]);
    }

    public function accept(Request $request, string $token, SupplierAccountService $accounts): RedirectResponse
    {
        $invitation = SupplierInvitation::findByToken($token);
        abort_unless($invitation, 404);
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['password' => 'mot de passe']);

        $user = $accounts->activate($invitation, $request->string('password'));

        // On ferme toute autre session ouverte dans ce navigateur, puis on connecte le fournisseur.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('supplier.dashboard')->with('success', 'Votre accès est activé. Bienvenue dans votre espace fournisseur !');
    }
}
