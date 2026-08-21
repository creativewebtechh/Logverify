<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbers', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            $table->string('category')->nullable(); // sms, voice, verification, whatsapp
            $table->string('number')->nullable();
            $table->string('masked_number'); // e.g. +1 (•••) •••-4821
            $table->decimal('price', 14, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('available'); // available, sold
            $table->string('provider')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'country']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbers');
    }
};
