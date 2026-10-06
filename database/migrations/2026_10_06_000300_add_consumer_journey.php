<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_returns', function (Blueprint $table) {
            $table->foreignId('scan_id')->nullable()->constrained('scans')->nullOnDelete();
            $table->string('action')->default('recycling');
            $table->string('condition')->nullable();
            $table->uuid('qr_token')->nullable()->unique();
            $table->decimal('confirmed_weight_kg', 8, 2)->nullable();
            $table->unsignedInteger('points_awarded')->default(0);
            $table->timestamp('deposited_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->index(['user_id', 'status']);
        });

        Schema::create('return_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['product_return_id', 'created_at']);
        });

        Schema::create('reward_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_return_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('type')->default('return_reward');
            $table->integer('points');
            $table->string('status')->default('pending');
            $table->string('description');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('consumer_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumer_notifications');
        Schema::dropIfExists('reward_transactions');
        Schema::dropIfExists('return_status_histories');

        Schema::table('product_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scan_id');
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn([
                'action', 'condition', 'qr_token', 'confirmed_weight_kg', 'points_awarded',
                'deposited_at', 'completed_at', 'cancelled_at',
            ]);
        });
    }
};
