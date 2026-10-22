<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le statut d'un fournisseur devient automatique : « pending » (en attente d'activation)
 * tant qu'il n'a pas activé son compte, « active » ensuite. Les fournisseurs suspendus restent suspendus.
 */
return new class extends Migration
{
    public function up(): void
    {
        $activated = DB::table('users')->where('role', 'supplier')->where(fn ($query) => $query->where('must_change_password', false)->orWhereNull('must_change_password'))->pluck('id');

        DB::table('suppliers')->where('status', '!=', 'suspended')->whereIn('user_id', $activated)->update(['status' => 'active']);
        DB::table('suppliers')->where('status', '!=', 'suspended')
            ->where(fn ($query) => $query->whereNull('user_id')->orWhereNotIn('user_id', $activated))
            ->update(['status' => 'pending']);
    }

    public function down(): void
    {
        DB::table('suppliers')->where('status', 'pending')->update(['status' => 'active']);
    }
};
