<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // bank | ewallet | qris
            $table->string('type');
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            // Logo bank/e-wallet, atau gambar QRIS statis
            $table->string('image')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('fee')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
