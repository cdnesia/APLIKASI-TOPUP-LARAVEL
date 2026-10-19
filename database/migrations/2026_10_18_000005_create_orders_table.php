<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice')->unique();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot data saat order dibuat, agar tidak berubah walau produk diubah
            $table->string('brand_name');
            $table->string('product_name');
            $table->string('sku');
            $table->string('customer_no');
            $table->string('customer_display');
            $table->string('whatsapp');
            $table->json('payment_snapshot')->nullable();

            $table->unsignedBigInteger('base_price');
            $table->unsignedBigInteger('selling_price');
            $table->unsignedInteger('fee')->default(0);
            $table->unsignedInteger('unique_code')->default(0);
            $table->unsignedBigInteger('total');

            $table->string('payment_status')->default('unpaid')->index();
            $table->string('payment_proof')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->string('status')->default('waiting')->index();
            // ref_id ke Digiflazz — dibuat ulang saat retry karena Digiflazz menolak ref_id ganda
            $table->string('ref_id')->nullable()->unique();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('sn')->nullable();
            $table->string('provider_rc')->nullable();
            $table->text('provider_message')->nullable();
            $table->unsignedBigInteger('provider_price')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
