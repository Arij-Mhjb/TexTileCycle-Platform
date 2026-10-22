<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('views_count')->default(0)->after('published_at');
        });

        Schema::create('article_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 100);
            $table->string('type', 10);
            $table->timestamps();
            $table->unique(['article_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_reactions');
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};
