<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_roadmaps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $t->foreignId('label_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->text('scope_description');
            $t->text('baseline');
            $t->text('objective');
            $t->date('starts_on');
            $t->date('target_date');
            $t->string('status')->default('active');
            $t->unsignedInteger('scope_version')->default(1);
            $t->string('template_version')->default('preparation-2026-v1');
            $t->json('label_snapshot');
            $t->timestamps();
        });
        Schema::create('label_roadmap_product', function (Blueprint $t) {
            $t->foreignId('label_roadmap_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->json('snapshot');
            $t->primary(['label_roadmap_id', 'product_id']);
        });
        Schema::create('roadmap_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('label_roadmap_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('instructions');
            $t->text('required_evidence');
            $t->unsignedInteger('position');
            $t->date('due_date');
            $t->string('owner_name');
            $t->text('goal');
            $t->string('status')->default('todo');
            $t->timestamps();
        });
        Schema::create('roadmap_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('roadmap_step_id')->constrained()->cascadeOnDelete();
            $t->foreignId('submitted_by')->constrained('users');
            $t->unsignedInteger('scope_version');
            $t->unsignedInteger('version');
            $t->text('actions');
            $t->text('results');
            $t->json('product_results');
            $t->json('files');
            $t->json('scope_snapshot');
            $t->json('step_snapshot');
            $t->json('material_record')->nullable();
            $t->foreignId('material_match_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status')->default('pending');
            $t->text('review_comment')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['roadmap_step_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_submissions');
        Schema::dropIfExists('roadmap_steps');
        Schema::dropIfExists('label_roadmap_product');
        Schema::dropIfExists('label_roadmaps');
    }
};
