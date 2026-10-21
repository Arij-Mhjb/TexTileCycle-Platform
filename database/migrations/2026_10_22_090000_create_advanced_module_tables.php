<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Métiers avancés des modules d'Ons :
 *  - carbon_targets       : objectifs carbone par collection (budget total et plafond par unité) ;
 *  - recycling_schedules  : collectes de rebuts programmées chaque mois ou chaque trimestre ;
 *  - certificate_alerts   : alertes déjà envoyées avant l'expiration d'un certificat (une par seuil) ;
 *  - recycling_loops      : numéro de facture / d'avoir et lien vers la collecte programmée d'origine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carbon_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('season', 40)->nullable();
            $table->decimal('budget_kg', 12, 2)->nullable();
            $table->decimal('max_unit_kg', 8, 3)->nullable();
            $table->json('planned_units')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamps();
        });

        Schema::create('recycling_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 20)->default('recover');
            $table->string('frequency', 20)->default('monthly');
            $table->unsignedTinyInteger('day_of_month')->default(1);
            $table->json('items');
            $table->text('brand_message')->nullable();
            $table->date('next_run_on')->index();
            $table->dateTime('last_run_at')->nullable();
            $table->unsignedBigInteger('last_loop_id')->nullable();
            $table->unsignedInteger('runs_count')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('certificate_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_origin_certificate_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('threshold');
            $table->dateTime('sent_at');
            $table->unique(['material_origin_certificate_id', 'threshold']);
        });

        Schema::table('recycling_loops', function (Blueprint $table) {
            $table->string('invoice_number', 40)->nullable()->unique();
            $table->dateTime('invoiced_at')->nullable();
            $table->unsignedBigInteger('recycling_schedule_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('recycling_loops', 'invoice_number')) {
            Schema::table('recycling_loops', function (Blueprint $table) {
                $table->dropUnique(['invoice_number']);
                $table->dropIndex(['recycling_schedule_id']);
                $table->dropColumn(['invoice_number', 'invoiced_at', 'recycling_schedule_id']);
            });
        }
        Schema::dropIfExists('certificate_alerts');
        Schema::dropIfExists('recycling_schedules');
        Schema::dropIfExists('carbon_targets');
    }
};
