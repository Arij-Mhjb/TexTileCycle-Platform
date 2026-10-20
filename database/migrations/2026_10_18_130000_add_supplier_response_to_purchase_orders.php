<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le fournisseur accepte ou refuse le bon de commande depuis le lien reçu par e-mail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('sent_at');
            $table->timestamp('declined_at')->nullable()->after('accepted_at');
            $table->text('decline_reason')->nullable()->after('supplier_note');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['accepted_at', 'declined_at', 'decline_reason']);
        });
    }
};
