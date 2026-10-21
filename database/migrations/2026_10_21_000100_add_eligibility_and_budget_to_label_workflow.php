<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_label_recommendations', function (Blueprint $table) {
            $table->json('eligibility_breakdown')->nullable()->after('readiness_score');
            $table->json('certification_estimate')->nullable()->after('eligibility_breakdown');
        });
        Schema::table('label_roadmaps', fn (Blueprint $table) => $table->json('certification_estimate')->nullable()->after('ai_assessment_snapshot'));
    }

    public function down(): void
    {
        Schema::table('product_label_recommendations', fn (Blueprint $table) => $table->dropColumn(['eligibility_breakdown', 'certification_estimate']));
        Schema::table('label_roadmaps', fn (Blueprint $table) => $table->dropColumn('certification_estimate'));
    }
};
