<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            // Kode metode Duitku (mis. SP, BC, DA). Kosong = pembayaran manual
            $table->string('gateway_code', 10)->nullable()->after('type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('gateway_reference')->nullable()->index()->after('payment_proof');
            $table->string('pay_code')->nullable()->after('gateway_reference'); // nomor VA / kode bayar
            $table->text('qr_string')->nullable()->after('pay_code');
            $table->string('checkout_url')->nullable()->after('qr_string');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['gateway_reference']);
            $table->dropColumn(['gateway_reference', 'pay_code', 'qr_string', 'checkout_url']);
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('gateway_code');
        });
    }
};
