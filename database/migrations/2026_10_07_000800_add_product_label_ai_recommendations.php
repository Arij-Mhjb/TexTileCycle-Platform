<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_label_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('readiness_score');
            $table->text('reason');
            $table->json('gaps');
            $table->json('target_composition')->nullable();
            $table->json('next_steps');
            $table->json('environmental_impacts');
            $table->json('reputation_impacts');
            $table->json('source_snapshot');
            $table->string('analysis_mode')->default('rules');
            $table->string('analysis_model')->nullable();
            $table->string('status')->default('suggested');
            $table->timestamp('analysed_at');
            $table->timestamps();
            $table->unique(['product_id', 'label_id']);
        });

        Schema::table('label_roadmaps', function (Blueprint $table) {
            $table->foreignId('product_label_recommendation_id')->nullable()->after('label_id')->constrained()->nullOnDelete();
            $table->json('specification')->nullable()->after('label_snapshot');
            $table->json('impact_summary')->nullable()->after('specification');
            $table->json('ai_assessment_snapshot')->nullable()->after('impact_summary');
        });

        Schema::table('roadmap_steps', fn (Blueprint $table) => $table->json('completion_points')->nullable()->after('required_evidence'));
    }

    public function down(): void
    {
        Schema::table('roadmap_steps', fn (Blueprint $table) => $table->dropColumn('completion_points'));
        Schema::table('label_roadmaps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_label_recommendation_id');
            $table->dropColumn(['specification', 'impact_summary', 'ai_assessment_snapshot']);
        });
        Schema::dropIfExists('product_label_recommendations');
    }
};
