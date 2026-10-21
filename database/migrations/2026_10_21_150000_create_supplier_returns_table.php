<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seconde vie de la matière entre marque et fournisseur : la marque renvoie un surplus,
 * une matière défectueuse ou une erreur de livraison ; le fournisseur la reprend et lui
 * donne une seconde vie (revente, recyclage, surcyclage) ; l'admin vérifie la preuve.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantity_kg', 10, 2);
            $table->string('reason', 30);
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->string('status', 30)->default('requested')->index();
            $table->string('destination', 30)->nullable();
            $table->date('pickup_at')->nullable();
            $table->text('supplier_note')->nullable();
            $table->decimal('resold_kg', 10, 2)->nullable();
            $table->decimal('recycled_kg', 10, 2)->nullable();
            $table->decimal('upcycled_kg', 10, 2)->nullable();
            $table->decimal('waste_kg', 10, 2)->nullable();
            $table->string('proof_path')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_returns');
    }
};
