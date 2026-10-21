<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boucle de recyclage marque ↔ fournisseur : la marque envoie à son fournisseur des pièces
 * défectueuses, des surplus ou des chutes ; le fournisseur les transforme en nouvelle matière
 * recyclée et la lui rend (restitution) ou la lui revend. La matière revient sous forme de lot traçable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('supplier_returns'); // ancienne version de la fonctionnalité (jamais publiée)

        Schema::create('recycling_loops', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('requested')->index();
            $table->text('brand_message')->nullable();
            $table->date('wished_pickup_at')->nullable();
            // Proposition du fournisseur
            $table->foreignId('output_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->decimal('expected_output_kg', 10, 2)->nullable();
            $table->string('mode', 20)->nullable(); // restitution | sale
            $table->decimal('unit_price', 10, 2)->nullable(); // TND / kg (frais de transformation ou prix de vente)
            $table->date('ready_by')->nullable();
            $table->text('supplier_message')->nullable();
            $table->text('decline_reason')->nullable();
            // Réalisation
            $table->decimal('received_kg', 10, 2)->nullable();
            $table->decimal('output_kg', 10, 2)->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('output_batch_id')->nullable()->constrained('supplier_batches')->nullOnDelete();
            $table->timestamp('proposed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('transformed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('recycling_loop_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recycling_loop_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // defective | surplus | offcuts
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->unsignedInteger('pieces')->nullable();
            $table->decimal('quantity_kg', 10, 2);
            $table->text('issue')->nullable();
            $table->string('photo_path')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recycling_loop_items');
        Schema::dropIfExists('recycling_loops');
    }
};
