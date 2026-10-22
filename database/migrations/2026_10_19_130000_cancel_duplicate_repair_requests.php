<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nettoyage des demandes de réparation en double (envois multiples pendant l'analyse IA) :
 * pour chaque article retourné, la première demande est conservée ; les doublons encore « Demandée »
 * passent en « Annulée ». Aucune ligne n'est supprimée.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('repair_orders')->whereNotNull('product_return_id')->where('status', '!=', 'cancelled')
            ->select('product_return_id', DB::raw('min(id) as keep_id'))->groupBy('product_return_id')->havingRaw('count(*) > 1')->get();

        foreach ($duplicates as $duplicate) {
            DB::table('repair_orders')->where('product_return_id', $duplicate->product_return_id)
                ->where('id', '!=', $duplicate->keep_id)->where('status', 'requested')
                ->update(['status' => 'cancelled', 'admin_note' => 'Doublon annulé automatiquement (demande envoyée plusieurs fois).', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Rien à défaire : les demandes annulées restent consultables.
    }
};
