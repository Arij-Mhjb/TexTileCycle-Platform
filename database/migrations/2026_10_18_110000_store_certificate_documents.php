<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La marque envoie le document du certificat ; l'IA en extrait les informations.
 * Les champs deviennent facultatifs (lus par l'IA, vérifiés par l'admin sur le document).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_origin_certificates', function (Blueprint $table) {
            $table->string('document_path')->nullable()->after('document_url');
            $table->string('type')->nullable()->change();
            $table->string('certificate_number')->nullable()->change();
            $table->string('issuer')->nullable()->change();
            $table->date('issued_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('material_origin_certificates', function (Blueprint $table) {
            $table->dropColumn('document_path');
        });
    }
};
