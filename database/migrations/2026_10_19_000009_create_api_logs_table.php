<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20)->index(); // digiflazz | duitku
            $table->string('direction', 3); // out = request kita, in = webhook/callback masuk
            $table->string('endpoint');
            $table->string('invoice')->nullable()->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->boolean('success')->index();
            $table->string('message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
