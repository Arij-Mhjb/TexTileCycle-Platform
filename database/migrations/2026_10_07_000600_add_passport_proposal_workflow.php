<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('passport_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('consumer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('admin_pending');
            $table->json('proposed_data');
            $table->json('field_provenance')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('brand_reviewed_at')->nullable();
            $table->text('brand_note')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['brand_id', 'status']);
        });

        Schema::create('passport_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passport_proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role')->nullable();
            $table->string('event');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['passport_proposal_id', 'created_at']);
        });

        Schema::table('collection_points', function (Blueprint $table) {
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('product_returns', function (Blueprint $table) {
            $table->timestamp('deposit_declared_at')->nullable();
        });

        Schema::create('return_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('path');
            $table->string('storage_disk')->nullable();
            $table->string('cloudinary_public_id')->nullable();
            $table->text('secure_url')->nullable();
            $table->string('delivery_type')->nullable();
            $table->string('format')->nullable();
            $table->unsignedBigInteger('version')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['product_return_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_evidences');
        Schema::table('product_returns', fn (Blueprint $table) => $table->dropColumn('deposit_declared_at'));
        Schema::table('collection_points', fn (Blueprint $table) => $table->dropConstrainedForeignId('operator_user_id'));
        Schema::dropIfExists('passport_events');
        Schema::dropIfExists('passport_proposals');
    }
};
