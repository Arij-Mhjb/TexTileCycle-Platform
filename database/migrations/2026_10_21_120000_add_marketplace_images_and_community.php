<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
            $table->string('try_on_category', 20)->default('top')->after('image_path');
        });

        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('media_path')->nullable();
            $table->foreignId('repost_of_id')->nullable()->constrained('community_posts')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->unique(['user_id', 'repost_of_id'], 'community_repost_user_unique');
        });

        Schema::create('community_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['community_post_id', 'user_id']);
        });

        Schema::create('community_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['community_post_id', 'created_at']);
        });

        Schema::create('community_post_mentions', function (Blueprint $table) {
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['community_post_id', 'user_id']);
        });

        Schema::create('community_comment_mentions', function (Blueprint $table) {
            $table->foreignId('community_comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['community_comment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_comment_mentions');
        Schema::dropIfExists('community_post_mentions');
        Schema::dropIfExists('community_comments');
        Schema::dropIfExists('community_post_likes');
        Schema::dropIfExists('community_posts');

        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'try_on_category']);
        });
    }
};
