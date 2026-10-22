<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('brand_members', function (Blueprint $t) {
            $t->id(); $t->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $t->string('name'); $t->string('email'); $t->string('department')->nullable(); $t->string('job_title')->nullable(); $t->string('phone',40)->nullable();
            $t->string('status')->default('invited'); $t->string('invitation_token_hash',64)->nullable(); $t->timestamp('invited_at')->nullable(); $t->timestamp('joined_at')->nullable(); $t->timestamps();
            $t->unique(['brand_id','email']);
        });
        Schema::table('roadmap_steps', function (Blueprint $t) {
            $t->foreignId('brand_member_id')->nullable()->after('owner_name')->constrained()->nullOnDelete();
            $t->string('priority')->default('normal')->after('brand_member_id');
            $t->text('assignment_comment')->nullable()->after('priority');
            $t->timestamp('assigned_at')->nullable()->after('assignment_comment');
        });
    }
    public function down(): void {
        Schema::table('roadmap_steps', function (Blueprint $t) { $t->dropConstrainedForeignId('brand_member_id'); $t->dropColumn(['priority','assignment_comment','assigned_at']); });
        Schema::dropIfExists('brand_members');
    }
};
