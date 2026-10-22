<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::table('label_roadmaps',function(Blueprint $t){$t->foreignId('brand_member_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();$t->string('assignment_priority')->default('normal')->after('brand_member_id');$t->text('assignment_comment')->nullable()->after('assignment_priority');$t->timestamp('assigned_at')->nullable()->after('assignment_comment');});}
 public function down():void{Schema::table('label_roadmaps',function(Blueprint $t){$t->dropConstrainedForeignId('brand_member_id');$t->dropColumn(['assignment_priority','assignment_comment','assigned_at']);});}
};
