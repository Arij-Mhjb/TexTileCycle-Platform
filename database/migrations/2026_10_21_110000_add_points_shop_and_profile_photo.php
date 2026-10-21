<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('email');
        });

        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->unsignedInteger('points_price')->nullable()->after('price');
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->unsignedInteger('points_spent')->default(0)->after('total_amount');
            $table->unique('marketplace_listing_id', 'marketplace_orders_listing_unique');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropUnique('marketplace_orders_listing_unique');
            $table->dropColumn('points_spent');
        });
        Schema::table('marketplace_listings', fn (Blueprint $table) => $table->dropColumn('points_price'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('profile_photo_path'));
    }
};
