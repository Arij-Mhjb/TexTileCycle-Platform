<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierInvitation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Accès fournisseur par invitation, comme sur les vraies plateformes :
 * la marque crée l'accès, l'application génère un lien d'activation secret (usage unique, 72 h)
 * que la marque transmet (WhatsApp, e-mail…). Le fournisseur choisit lui-même son mot de passe :
 * la marque ne le voit jamais, et un lien déjà utilisé ou expiré ne sert plus à rien.
 */
class SupplierAccountService
{
    /** @return array{email: string, url: string, expires_at: Carbon, created: bool} */
    public function invite(Supplier $supplier, ?User $by = null): array
    {
        if (! $supplier->email) {
            throw ValidationException::withMessages(['email' => 'Ajoutez l’e-mail du fournisseur : ce sera son identifiant de connexion.']);
        }

        $user = $supplier->user ?? User::where('email', $supplier->email)->first();
        if ($user && $user->role !== 'supplier') {
            throw ValidationException::withMessages(['email' => 'Cet e-mail est déjà utilisé par un autre type de compte TexTileCycle.']);
        }

        return DB::transaction(function () use ($supplier, $user, $by) {
            $created = ! $user;
            if (! $user) {
                $user = new User(['name' => $supplier->contact_name ?: $supplier->name, 'email' => $supplier->email, 'role' => 'supplier']);
                // Mot de passe aléatoire que personne ne connaît : le compte n'est utilisable qu'après activation.
                $user->password = Hash::make(Str::random(64));
                $user->forceFill(['must_change_password' => true]);
                $user->save();
            }
            $supplier->update(['user_id' => $user->id]);

            // Une seule invitation valable à la fois : les précédentes sont annulées.
            $supplier->invitations()->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);

            $token = Str::random(48);
            $invitation = $supplier->invitations()->create([
                'user_id' => $user->id,
                'invited_by' => $by?->id,
                'token_hash' => SupplierInvitation::hashToken($token),
                'expires_at' => now()->addHours(SupplierInvitation::VALIDITY_HOURS),
            ]);

            return ['email' => $user->email, 'url' => route('supplier.invitation.show', $token), 'expires_at' => $invitation->expires_at, 'created' => $created];
        });
    }

    /** Le fournisseur active son compte en choisissant son mot de passe. */
    public function activate(SupplierInvitation $invitation, string $password): User
    {
        if (! $invitation->isUsable()) {
            throw ValidationException::withMessages(['password' => $invitation->unusableReason()]);
        }

        return DB::transaction(function () use ($invitation, $password) {
            $user = $invitation->user;
            $user->forceFill([
                'password' => Hash::make($password),
                'must_change_password' => false,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            $invitation->update(['used_at' => now()]);

            return $user;
        });
    }

    /** Message prêt à envoyer (WhatsApp, SMS, e-mail) : le lien, jamais de mot de passe. */
    public function invitationMessage(Supplier $supplier, array $invitation): string
    {
        return 'Bonjour, '.$supplier->brand->name.' vous invite sur TexTileCycle pour gérer ses commandes (accepter, livrer, certificats). '
            .'Activez votre accès et choisissez votre mot de passe ici : '.$invitation['url']
            .' (lien personnel, valable jusqu’au '.$invitation['expires_at']->format('d/m/Y à H:i').'). Identifiant : '.$invitation['email'];
    }
}
