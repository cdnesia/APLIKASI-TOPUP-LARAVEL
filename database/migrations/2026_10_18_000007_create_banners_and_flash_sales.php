<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Banner promo yang tampil sebagai slide di beranda
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image');
            $table->string('link_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('brands', function (Blueprint $table) {
            // Gambar potret 3:4 untuk kartu katalog
            $table->string('cover')->nullable()->after('banner');
            $table->string('publisher')->nullable()->after('name');
        });

        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('quota');
            // Dihitung saat order dibuat, dikembalikan bila order batal/kedaluwarsa
            $table->unsignedInteger('sold')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('flash_sale_id')->nullable()->after('payment_method_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('flash_sale_id'));
        Schema::dropIfExists('flash_sales');
        Schema::table('brands', fn (Blueprint $table) => $table->dropColumn(['cover', 'publisher']));
        Schema::dropIfExists('banners');
    }
};
