<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing brands retain access. Only new registrations enter the review workflow.
        Schema::table('brands', function (Blueprint $table) {
            $table->string('onboarding_status')->default('approved');
        });
        Schema::create('brand_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('questionnaire_version')->default('2026-10-v1');
            $table->json('answers');
            $table->string('analysis_status')->default('pending');
            $table->string('analysis_model')->nullable();
            $table->json('recommendations')->nullable();
            $table->text('analysis_summary')->nullable();
            $table->timestamp('analysed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->json('approved_label_snapshot')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('brand_label', function (Blueprint $table) {
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['brand_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_label');
        Schema::dropIfExists('brand_applications');
        Schema::table('brands', fn (Blueprint $table) => $table->dropColumn('onboarding_status'));
    }
};
