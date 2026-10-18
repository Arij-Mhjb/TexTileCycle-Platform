<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\User;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Accès fournisseur : la marque ouvre un compte (rôle « supplier ») à son fournisseur.
 * Le mot de passe provisoire est envoyé uniquement par SMS au fournisseur (jamais affiché à la marque)
 * et doit être changé à la première connexion.
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
        if (! app(SmsService::class)->normalize($supplier->phone)) {
            throw ValidationException::withMessages(['phone' => 'Ajoutez un numéro de téléphone valide : les identifiants sont envoyés uniquement par SMS au fournisseur.']);
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

    /** Texte du SMS envoyé au fournisseur (court : 1 à 2 SMS). */
    public function smsText(Supplier $supplier, array $credentials, bool $masked = false): string
    {
        return $supplier->brand->name.' vous ouvre un accès fournisseur TexTileCycle. '
            .'Connexion : '.route('login').' Identifiant : '.$credentials['email'].' Mot de passe provisoire : '
            .($masked ? '••••-••••-••••' : $credentials['password']);
    }

    /** Ex. Tx7k-P4mQ-a9Zr : lisible, envoyé par SMS. */
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
