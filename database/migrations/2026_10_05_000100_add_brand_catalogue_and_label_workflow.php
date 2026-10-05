<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('origin')->nullable()->after('description');
            $table->string('barcode')->nullable()->unique()->after('sku');
            $table->string('qr_code')->nullable()->unique()->after('barcode');
            $table->unsignedTinyInteger('recyclability_score')->nullable();
            $table->text('recycling_instructions')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('organization')->nullable();
            $table->enum('pillar', ['environment', 'animals', 'social', 'circularity']);
            $table->text('description');
            $table->string('official_url')->nullable();
            $table->string('cost_hint')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('label_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('label_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->timestamp('submitted_at')->nullable();
        });

        Schema::table('evidences', function (Blueprint $table) {
            $table->foreignId('label_step_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->text('declaration')->nullable();
            $table->text('admin_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->text('result_summary')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('scans', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_id'));
        Schema::table('evidences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('label_step_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['file_path', 'declaration', 'admin_comment', 'reviewed_at']);
        });
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('label_id');
            $table->dropColumn(['score', 'submitted_at']);
        });
        Schema::dropIfExists('label_steps');
        Schema::dropIfExists('labels');
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropUnique(['slug']);
            $table->dropUnique(['barcode']);
            $table->dropUnique(['qr_code']);
            $table->dropColumn(['slug', 'origin', 'barcode', 'qr_code', 'recyclability_score', 'recycling_instructions', 'is_active']);
        });
        Schema::dropIfExists('brands');
    }
};
