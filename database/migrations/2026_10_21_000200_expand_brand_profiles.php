<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('sector')->nullable()->after('description');
            $table->unsignedSmallInteger('founded_year')->nullable()->after('sector');
            $table->string('employee_range')->nullable()->after('founded_year');
            $table->string('phone', 40)->nullable()->after('contact_email');
            $table->string('city', 120)->nullable()->after('country');
            $table->string('address')->nullable()->after('city');
            $table->string('linkedin_url')->nullable()->after('website');
            $table->text('target_markets')->nullable()->after('commitments');
        });
    }

    public function down(): void
    {
        Schema::table('brands', fn (Blueprint $table) => $table->dropColumn([
            'legal_name', 'sector', 'founded_year', 'employee_range', 'phone', 'city', 'address', 'linkedin_url', 'target_markets',
        ]));
    }
};
