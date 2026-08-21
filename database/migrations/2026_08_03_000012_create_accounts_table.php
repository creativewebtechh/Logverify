<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // instagram, tiktok, whatsapp, telegram, facebook, twitter, gmail
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('available'); // available, sold
            $table->string('provider')->nullable();
            $table->string('provider_service_id', 100)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
