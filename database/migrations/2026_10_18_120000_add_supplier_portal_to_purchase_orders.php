<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portail fournisseur : la commande est envoyée par e-mail avec un lien signé,
 * le fournisseur déclare l'expédition (lot + certificat) sans créer de compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('received_at');
            $table->timestamp('shipped_at')->nullable()->after('sent_at');
            $table->text('supplier_note')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'shipped_at', 'supplier_note']);
        });
    }
};
