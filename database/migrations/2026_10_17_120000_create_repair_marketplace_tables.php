<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Réparation / revente / marketplace circulaire :
 * seconde vie des produits après un retour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city', 100);
            $table->string('address')->nullable();
            $table->string('specialties')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->unsignedSmallInteger('capacity_per_week')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('repair_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_return_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('repair_center_id')->nullable()->constrained()->nullOnDelete();
            $table->text('issue_description');
            $table->string('decision')->nullable();
            $table->string('status')->default('requested');
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('final_cost', 10, 2)->nullable();
            $table->unsignedInteger('reward_points')->default(0);
            $table->text('admin_note')->nullable();
            $table->timestamp('diagnosed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refurbishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_order_id')->constrained()->cascadeOnDelete();
            $table->text('actions');
            $table->char('grade', 1)->default('B');
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('technician')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('repair_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->char('condition_grade', 1)->default('B');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('TND');
            $table->string('status')->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
        });

        Schema::create('resale_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('offered_price', 10, 2);
            $table->string('message', 500)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trade_in_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable();
            $table->string('condition');
            $table->text('description')->nullable();
            $table->unsignedInteger('proposed_points')->nullable();
            $table->string('status')->default('submitted');
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('evaluation_note')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_in_requests');
        Schema::dropIfExists('resale_offers');
        Schema::dropIfExists('marketplace_listings');
        Schema::dropIfExists('refurbishments');
        Schema::dropIfExists('repair_orders');
        Schema::dropIfExists('repair_centers');
    }
};
