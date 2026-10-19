<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // Kategori mengikuti Digiflazz: Games, Pulsa, Data, E-Money, PLN, Voucher, dst.
            $table->string('category')->index();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('field_label')->default('ID Pelanggan');
            // Diisi untuk game yang butuh Zone/Server ID (mis. Mobile Legends)
            $table->string('zone_label')->nullable();
            $table->string('input_hint')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
