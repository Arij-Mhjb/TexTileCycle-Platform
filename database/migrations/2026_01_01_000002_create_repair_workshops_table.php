<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_partner_id')->nullable()->constrained('repair_partners')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city');
            $table->string('address');
            $table->string('specialties')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('capacity_per_week')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_workshops');
    }
};
