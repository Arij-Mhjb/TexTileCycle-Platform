<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('chatbot_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_category_id')->constrained()->cascadeOnDelete();
            $table->string('question', 255);
            $table->string('keywords', 255)->nullable();
            $table->longText('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('chatbot_query_logs', function (Blueprint $table) {
            $table->id();
            $table->string('question_text', 500);
            $table->foreignId('matched_question_id')->nullable()->constrained('chatbot_questions')->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_query_logs');
        Schema::dropIfExists('chatbot_questions');
        Schema::dropIfExists('chatbot_categories');
    }
};
