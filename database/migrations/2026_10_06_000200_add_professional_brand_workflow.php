<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('country')->nullable();
            $table->text('commitments')->nullable();
            $table->string('contact_email')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedTinyInteger('completion_score')->default(0);
            $table->unsignedInteger('current_version')->default(1);
            $table->text('review_comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->index(['brand_id', 'status']);
        });

        Schema::table('material_product', function (Blueprint $table) {
            $table->string('component')->default('main');
            $table->string('supplier')->nullable();
            $table->string('sourcing_type')->nullable();
            $table->string('evidence_path')->nullable();
        });

        Schema::table('label_steps', function (Blueprint $table) {
            $table->unsignedTinyInteger('weight')->default(25);
        });

        Schema::table('evidences', function (Blueprint $table) {
            $table->foreignId('supersedes_id')->nullable()->constrained('evidences')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
        });

        Schema::create('product_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->string('reason')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['product_id', 'version']);
        });

        Schema::create('evidence_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['brand_id', 'created_at']);
        });

        Schema::create('brand_notifications', function (Blueprint $table) {
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
        Schema::dropIfExists('brand_notifications');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('evidence_comments');
        Schema::dropIfExists('product_versions');
        Schema::table('evidences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supersedes_id');
            $table->dropColumn('version');
        });
        Schema::table('label_steps', fn (Blueprint $table) => $table->dropColumn('weight'));
        Schema::table('material_product', fn (Blueprint $table) => $table->dropColumn(['component','supplier','sourcing_type','evidence_path']));
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['completion_score','current_version','review_comment','submitted_at','verified_at','published_at','archived_at','deleted_at']);
        });
        Schema::table('brands', fn (Blueprint $table) => $table->dropColumn(['country','commitments','contact_email']));
    }
};
