<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('passport_proposal_collection_point', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passport_proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_point_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['passport_proposal_id', 'collection_point_id'], 'proposal_point_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_proposal_collection_point');
    }
};
