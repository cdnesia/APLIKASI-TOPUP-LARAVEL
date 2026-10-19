<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            // Gambar latar header halaman order
            $table->string('banner')->nullable()->after('image');
        });

        Schema::table('products', function (Blueprint $table) {
            // Ikon nominal (mis. gambar diamond); kosong = pakai logo brand
            $table->string('image')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('brands', fn (Blueprint $table) => $table->dropColumn('banner'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('image'));
    }
};
