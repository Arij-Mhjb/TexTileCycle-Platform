<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roadmap_steps', function (Blueprint $table) {
            $table->string('material_stage')->nullable();
        });
        Schema::table('material_requests', function (Blueprint $table) {
            $table->foreignId('roadmap_step_id')->nullable()->constrained('roadmap_steps')->restrictOnDelete();
            $table->unsignedInteger('roadmap_scope_version')->nullable();
        });
        Schema::create('material_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_match_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('label_roadmap_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('scope_version')->nullable();
            $table->string('status')->default('stored');
            $table->decimal('input_kg', 12, 2)->nullable();
            $table->decimal('recycled_output_kg', 12, 2)->nullable();
            $table->decimal('incorporated_kg', 12, 2)->nullable();
            $table->json('product_quantities')->nullable();
            $table->timestamps();
        });
        Schema::create('material_usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_usage_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status');
            $table->text('note');
            $table->json('files');
            $table->json('measurements');
            $table->timestamps();
        });
        // Existing plans keep their evidence and acquire explicit circularity stages.
        foreach (['Tracer les matières et leur collecte' => 'collection', 'Documenter la transformation' => 'transformation', 'Produire et contrôler un lot pilote' => 'incorporation'] as $title => $stage) {
            DB::table('roadmap_steps')->where('title', $title)->update(['material_stage' => $stage]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('material_usage_events');
        Schema::dropIfExists('material_usages');
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('roadmap_step_id');
            $table->dropColumn('roadmap_scope_version');
        });
        Schema::table('roadmap_steps', fn (Blueprint $table) => $table->dropColumn('material_stage'));
    }
};
