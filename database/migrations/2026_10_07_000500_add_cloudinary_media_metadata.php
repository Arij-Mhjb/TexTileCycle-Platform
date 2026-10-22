<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('image_storage_disk')->nullable()->after('image_path');
            $table->string('image_cloudinary_public_id')->nullable()->after('image_storage_disk');
            $table->text('image_secure_url')->nullable()->after('image_cloudinary_public_id');
            $table->string('image_delivery_type')->nullable()->after('image_secure_url');
            $table->string('image_format', 20)->nullable()->after('image_delivery_type');
            $table->unsignedBigInteger('image_version')->nullable()->after('image_format');
        });

        Schema::table('scan_assets', function (Blueprint $table) {
            $table->string('storage_disk')->nullable()->after('path');
            $table->string('cloudinary_public_id')->nullable()->after('storage_disk');
            $table->text('secure_url')->nullable()->after('cloudinary_public_id');
            $table->string('delivery_type')->nullable()->after('secure_url');
            $table->string('format', 20)->nullable()->after('delivery_type');
            $table->unsignedBigInteger('version')->nullable()->after('format');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('image_storage_disk')->nullable()->after('image_path');
            $table->string('image_cloudinary_public_id')->nullable()->after('image_storage_disk');
            $table->text('image_secure_url')->nullable()->after('image_cloudinary_public_id');
            $table->string('image_delivery_type')->nullable()->after('image_secure_url');
            $table->string('image_format', 20)->nullable()->after('image_delivery_type');
            $table->unsignedBigInteger('image_version')->nullable()->after('image_format');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn([
            'image_storage_disk', 'image_cloudinary_public_id', 'image_secure_url',
            'image_delivery_type', 'image_format', 'image_version',
        ]));
        Schema::table('scan_assets', fn (Blueprint $table) => $table->dropColumn([
            'storage_disk', 'cloudinary_public_id', 'secure_url', 'delivery_type', 'format', 'version',
        ]));
        Schema::table('scans', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn([
                'uuid', 'image_storage_disk', 'image_cloudinary_public_id', 'image_secure_url',
                'image_delivery_type', 'image_format', 'image_version',
            ]);
        });
    }
};
