<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Impact ESG / analyse environnementale :
 * CO2, eau, énergie, déchets par matière, produit et marque.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impact_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('unit', 30);
            $table->decimal('weight', 4, 2)->default(0.25);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('impact_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('impact_category_id')->constrained()->cascadeOnDelete();
            $table->decimal('value_per_kg', 12, 4);
            $table->string('source')->nullable();
            $table->timestamps();
            $table->unique(['material_id', 'impact_category_id']);
        });

        Schema::create('product_impact_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('season', 40)->nullable();
            $table->decimal('product_weight_kg', 8, 3);
            $table->decimal('carbon_kg', 12, 3)->default(0);
            $table->decimal('water_l', 14, 2)->default(0);
            $table->decimal('energy_kwh', 12, 3)->default(0);
            $table->decimal('waste_kg', 12, 3)->default(0);
            $table->decimal('avoided_waste_kg', 12, 3)->default(0);
            $table->unsignedTinyInteger('circularity_bonus')->default(0);
            $table->unsignedTinyInteger('impact_score')->default(0);
            $table->char('grade', 1)->default('E');
            $table->string('status')->default('draft');
            $table->foreignId('assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('carbon_footprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_impact_report_id')->constrained()->cascadeOnDelete();
            $table->string('stage');
            $table->decimal('kg_co2e', 12, 3);
            $table->timestamps();
        });

        Schema::create('water_footprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_impact_report_id')->constrained()->cascadeOnDelete();
            $table->string('stage');
            $table->decimal('liters', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_footprints');
        Schema::dropIfExists('carbon_footprints');
        Schema::dropIfExists('product_impact_reports');
        Schema::dropIfExists('impact_metrics');
        Schema::dropIfExists('impact_categories');
    }
};
