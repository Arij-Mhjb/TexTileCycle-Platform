<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('points_cost');
            $table->string('partner_name')->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_item_id')->constrained()->restrictOnDelete();
            $table->string('reference')->unique();
            $table->string('code')->unique();
            $table->unsignedInteger('points_spent');
            $table->string('status')->default('issued');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('consumer_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_return_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('repair_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trade_in_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('category');
            $table->string('subject');
            $table->text('description');
            $table->string('status')->default('open');
            $table->string('priority')->default('normal');
            $table->text('admin_response')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('repair_orders', function (Blueprint $table) {
            $table->string('consumer_decision')->nullable()->after('decision');
            $table->dateTime('consumer_decided_at')->nullable()->after('consumer_decision');
            $table->dateTime('preferred_appointment_at')->nullable()->after('consumer_decided_at');
        });

        Schema::table('trade_in_requests', function (Blueprint $table) {
            $table->foreignId('collection_point_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('qr_token')->nullable()->unique()->after('status');
            $table->json('deposit_evidence')->nullable()->after('qr_token');
            $table->dateTime('deposited_at')->nullable()->after('deposit_evidence');
            $table->dateTime('received_at')->nullable()->after('deposited_at');
        });

        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('marketplace_listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('resale_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('collection_point_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('TND');
            $table->string('fulfilment_method')->default('pickup');
            $table->string('payment_method')->default('pay_on_pickup');
            $table->text('delivery_address')->nullable();
            $table->string('status')->default('awaiting_checkout');
            $table->dateTime('checked_out_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_orders');
        Schema::table('trade_in_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collection_point_id');
            $table->dropColumn(['qr_token', 'deposit_evidence', 'deposited_at', 'received_at']);
        });
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->dropColumn(['consumer_decision', 'consumer_decided_at', 'preferred_appointment_at']);
        });
        Schema::dropIfExists('consumer_claims');
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('reward_items');
    }
};
