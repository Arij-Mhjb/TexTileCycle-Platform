<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Résultats des fonctionnalités IA des modules Fournisseurs, Impact ESG et Seconde vie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->json('ai_risk')->nullable()->after('compliance_score');
        });
        Schema::table('material_origin_certificates', function (Blueprint $table) {
            $table->json('ai_extraction')->nullable()->after('document_url');
        });
        Schema::table('product_impact_reports', function (Blueprint $table) {
            $table->json('ai_recommendations')->nullable()->after('notes');
        });
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->json('ai_diagnosis')->nullable()->after('admin_note');
        });
    }

    public function down(): void
    {
        Schema::table('repair_orders', fn (Blueprint $table) => $table->dropColumn('ai_diagnosis'));
        Schema::table('product_impact_reports', fn (Blueprint $table) => $table->dropColumn('ai_recommendations'));
        Schema::table('material_origin_certificates', fn (Blueprint $table) => $table->dropColumn('ai_extraction'));
        Schema::table('suppliers', fn (Blueprint $table) => $table->dropColumn('ai_risk'));
    }
};
