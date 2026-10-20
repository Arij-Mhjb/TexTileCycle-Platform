<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Fournisseurs & traçabilité des lots :
 * qui a fourni quelle matière, d'où, quand et avec quelle certification.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('country', 100);
            $table->string('city', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('certifications')->nullable();
            $table->string('status')->default('active');
            $table->unsignedTinyInteger('compliance_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['brand_id', 'name']);
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('ordered');
            $table->date('ordered_at');
            $table->date('expected_at')->nullable();
            $table->date('received_at')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('TND');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_code')->unique();
            $table->decimal('quantity_kg', 10, 2);
            $table->string('origin_country', 100);
            $table->date('production_date')->nullable();
            $table->date('received_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('verification_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('material_origin_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_batch_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('certificate_number');
            $table->string('issuer');
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->string('document_url')->nullable();
            $table->string('status')->default('pending');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_supplier_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_batch_id')->constrained()->cascadeOnDelete();
            $table->string('component')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('conformity_status')->default('unchecked');
            $table->text('conformity_note')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'supplier_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier_links');
        Schema::dropIfExists('material_origin_certificates');
        Schema::dropIfExists('supplier_batches');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
