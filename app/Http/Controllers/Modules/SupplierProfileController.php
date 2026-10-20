<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * « Mon compte » du fournisseur : ses coordonnées, son identifiant de connexion et son mot de passe, sur une seule page.
 * Un même compte peut fournir plusieurs marques : ses coordonnées sont mises à jour sur toutes ses fiches.
 * Les notes internes et le statut restent du ressort de la marque.
 */
class SupplierProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $suppliers = Supplier::with('brand')->where('user_id', $user->id)->orderBy('id')->get();

        return view('modules.suppliers.area.account', [
            'user' => $user,
            'profile' => $suppliers->first(),
            'suppliers' => $suppliers,
            'forced' => (bool) $user->must_change_password,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:40',
            'country' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
        ], [], ['name' => 'nom et prénom', 'phone' => 'téléphone', 'country' => 'pays', 'city' => 'ville', 'address' => 'adresse']);

        DB::transaction(function () use ($request, $data) {
            $request->user()->update(['name' => $data['name']]);
            foreach (Supplier::where('user_id', $request->user()->id)->get() as $supplier) {
                // Le nom doit rester unique chez chaque marque.
                $nameTaken = Supplier::where('brand_id', $supplier->brand_id)->where('name', $data['name'])->whereKeyNot($supplier->id)->exists();
                $supplier->update($nameTaken ? collect($data)->except('name')->all() : $data);
            }
        });

        return to_route('supplier.account.edit')->with('success', 'Vos informations sont à jour chez tous vos clients.');
    }

    /** Changer l'e-mail de connexion : confirmation par le mot de passe actuel. */
    public function updateEmail(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validateWithBag('email', [
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ], ['email.unique' => 'Cet e-mail est déjà utilisé par un autre compte.', 'current_password.current_password' => 'Mot de passe actuel incorrect.'],
            ['email' => 'nouvel e-mail', 'current_password' => 'mot de passe actuel']);

        DB::transaction(function () use ($user, $data) {
            $user->forceFill(['email' => $data['email'], 'email_verified_at' => now()])->save();
            Supplier::where('user_id', $user->id)->update(['email' => $data['email']]);
        });

        return to_route('supplier.account.edit')->with('success', 'Votre identifiant de connexion est maintenant '.$data['email'].'.');
    }

    /** Changer le mot de passe (le mot de passe actuel est demandé, sauf s'il était provisoire). */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $rules = ['password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]];
        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }
        $request->validateWithBag('password', $rules, ['current_password.current_password' => 'Mot de passe actuel incorrect.'],
            ['password' => 'nouveau mot de passe', 'current_password' => 'mot de passe actuel']);

        $wasForced = (bool) $user->must_change_password;
        $user->forceFill(['password' => Hash::make($request->password), 'must_change_password' => false])->save();

        return $wasForced
            ? to_route('supplier.dashboard')->with('success', 'Mot de passe enregistré. Bienvenue dans votre espace fournisseur !')
            : to_route('supplier.account.edit')->with('success', 'Mot de passe modifié.');
    }
}
