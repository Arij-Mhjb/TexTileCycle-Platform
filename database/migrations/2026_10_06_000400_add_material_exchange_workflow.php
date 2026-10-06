<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->string('ai_status')->default('pending');
            $table->json('extracted_data')->nullable();
            $table->text('ai_explanation')->nullable();
            $table->timestamp('consumer_confirmed_at')->nullable();
        });

        Schema::create('scan_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('path');
            $table->longText('extracted_text')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->timestamps();
            $table->index(['scan_id', 'type']);
        });

        Schema::table('product_returns', function (Blueprint $table) {
            $table->string('admin_review_status')->default('pending');
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_review_note')->nullable();
            $table->timestamp('admin_verified_at')->nullable();
            $table->string('logistics_mode')->default('hub_transfer');
        });

        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('material_name');
            $table->decimal('minimum_percentage', 5, 2)->default(0);
            $table->json('categories')->nullable();
            $table->json('accepted_conditions')->nullable();
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('remaining_kg', 10, 2);
            $table->string('city')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->unsignedInteger('reward_points')->default(0);
            $table->string('status')->default('open');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'material_name']);
        });

        Schema::create('material_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_return_id')->constrained()->cascadeOnDelete();
            $table->decimal('match_score', 5, 2)->default(0);
            $table->string('status')->default('suggested');
            $table->uuid('transfer_token')->nullable()->unique();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamp('brand_received_at')->nullable();
            $table->timestamp('brand_verified_at')->nullable();
            $table->text('brand_review_note')->nullable();
            $table->timestamps();
            $table->unique(['material_request_id', 'product_return_id']);
            $table->index(['product_return_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_matches');
        Schema::dropIfExists('material_requests');

        Schema::table('product_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_reviewed_by');
            $table->dropColumn(['admin_review_status', 'admin_review_note', 'admin_verified_at', 'logistics_mode']);
        });

        Schema::dropIfExists('scan_assets');

        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn(['ai_status', 'extracted_data', 'ai_explanation', 'consumer_confirmed_at']);
        });
    }
};
