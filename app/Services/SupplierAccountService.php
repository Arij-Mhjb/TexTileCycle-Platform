<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Accès fournisseur : la marque ouvre un compte (rôle « supplier ») à son fournisseur.
 * Le mot de passe provisoire n'est affiché qu'une fois et doit être changé à la première connexion.
 */
class SupplierAccountService
{
    /** @return array{email: string, password: string, phone: ?string, created: bool} */
    public function grantAccess(Supplier $supplier): array
    {
        if (! $supplier->email) {
            throw ValidationException::withMessages(['email' => 'Ajoutez l’e-mail du fournisseur pour lui créer un accès.']);
        }

        $user = $supplier->user ?? User::where('email', $supplier->email)->first();
        if ($user && $user->role !== 'supplier') {
            throw ValidationException::withMessages(['email' => 'Cet e-mail est déjà utilisé par un autre type de compte TexTileCycle.']);
        }

        $password = $this->temporaryPassword();
        $created = ! $user;
        if (! $user) {
            $user = new User(['name' => $supplier->contact_name ?: $supplier->name, 'email' => $supplier->email, 'role' => 'supplier']);
            $user->email_verified_at = now();
        }
        $user->password = Hash::make($password);
        $user->forceFill(['must_change_password' => true])->save();
        $supplier->update(['user_id' => $user->id]);

        return ['email' => $user->email, 'password' => $password, 'phone' => $supplier->phone, 'created' => $created];
    }

    /** Ex. Tx7k-P4mQ-a9Zr : lisible, à transmettre par téléphone ou WhatsApp. */
    private function temporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $parts = [];
        for ($i = 0; $i < 3; $i++) {
            $parts[] = collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->join('');
        }

        return implode('-', $parts);
    }
}
