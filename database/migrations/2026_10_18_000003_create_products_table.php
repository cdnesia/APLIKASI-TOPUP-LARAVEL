<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            // buyer_sku_code dari Digiflazz
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('type')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->unsignedBigInteger('selling_price');
            // Jika true, harga jual tidak ditimpa saat sinkronisasi
            $table->boolean('price_locked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('provider_active')->default(true);
            $table->boolean('unlimited_stock')->default(true);
            $table->unsignedInteger('stock')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
