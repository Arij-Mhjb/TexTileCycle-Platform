<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('material_matches', function (Blueprint $table) {
            $table->string('ai_verdict')->nullable()->after('match_score');
            $table->decimal('ai_confidence', 5, 2)->nullable()->after('ai_verdict');
            $table->text('ai_reason')->nullable()->after('ai_confidence');
            $table->json('ai_details')->nullable()->after('ai_reason');
            $table->string('ai_source')->nullable()->after('ai_details');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_source');
            $table->index(['ai_verdict', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('material_matches', function (Blueprint $table) {
            $table->dropIndex(['ai_verdict', 'status']);
            $table->dropColumn(['ai_verdict', 'ai_confidence', 'ai_reason', 'ai_details', 'ai_source', 'ai_analyzed_at']);
        });
    }
};
