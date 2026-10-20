<?php

namespace App\Services;

use App\Models\BrandNotification;
use App\Models\ConsumerNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Point d'entrée unique des notifications des trois modules.
 *
 * Les consommateurs utilisent la table consumer_notifications ; tous les autres rôles
 * (marque, fournisseur, admin) utilisent brand_notifications, déjà indexée par user_id.
 */
class NotificationCenter
{
    public static function queryFor(User $user): Builder
    {
        return self::modelFor($user->role)::query()->where('user_id', $user->id);
    }

    public static function modelFor(?string $role): string
    {
        return $role === 'consumer' ? ConsumerNotification::class : BrandNotification::class;
    }

    public function send(?int $userId, string $type, string $title, string $message, ?string $url = null): void
    {
        if (! $userId || ! ($user = User::find($userId))) {
            return;
        }

        self::modelFor($user->role)::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
        ]);
    }

    public function toAdmins(string $type, string $title, string $message, ?string $url = null): void
    {
        User::where('role', 'admin')->pluck('id')
            ->each(fn (int $id) => $this->send($id, $type, $title, $message, $url));
    }
}
