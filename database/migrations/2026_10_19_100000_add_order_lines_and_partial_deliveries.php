<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Commandes fournisseurs « réelles » :
 * - lignes de commande (matière, quantité, prix unitaire négocié) ;
 * - contre-proposition du fournisseur (quantité confirmée par ligne, nouvelle date) ;
 * - livraisons partielles : chaque lot expédié est rattaché à une ligne ;
 * - clôture avec reliquat et journal des événements de la commande.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('confirmed_kg', 10, 2)->nullable();
            $table->decimal('unit_price', 10, 3)->nullable();
            $table->timestamps();
            $table->unique(['purchase_order_id', 'material_id']);
        });

        Schema::table('supplier_batches', function (Blueprint $table) {
            $table->foreignId('purchase_order_line_id')->nullable()->after('purchase_order_id')->constrained()->nullOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('proposed_expected_at')->nullable()->after('expected_at');
            $table->timestamp('countered_at')->nullable()->after('accepted_at');
            $table->timestamp('closed_short_at')->nullable()->after('received_at');
            $table->string('close_reason', 500)->nullable()->after('closed_short_at');
        });

        Schema::create('purchase_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor', 20);
            $table->string('type', 40);
            $table->text('message');
            $table->timestamps();
        });

        // Reprise des commandes existantes : une ligne par matière déjà livrée, lots rattachés.
        $groups = DB::table('supplier_batches')->whereNotNull('purchase_order_id')
            ->selectRaw('purchase_order_id, material_id, sum(quantity_kg) as kg')
            ->groupBy('purchase_order_id', 'material_id')->get();
        foreach ($groups as $group) {
            $lineId = DB::table('purchase_order_lines')->insertGetId([
                'purchase_order_id' => $group->purchase_order_id, 'material_id' => $group->material_id,
                'quantity_kg' => $group->kg, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('supplier_batches')->where('purchase_order_id', $group->purchase_order_id)
                ->where(fn ($query) => $group->material_id ? $query->where('material_id', $group->material_id) : $query->whereNull('material_id'))
                ->update(['purchase_order_line_id' => $lineId]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_events');
        Schema::table('purchase_orders', fn (Blueprint $table) => $table->dropColumn(['proposed_expected_at', 'countered_at', 'closed_short_at', 'close_reason']));
        Schema::table('supplier_batches', fn (Blueprint $table) => $table->dropConstrainedForeignId('purchase_order_line_id'));
        Schema::dropIfExists('purchase_order_lines');
    }
};
