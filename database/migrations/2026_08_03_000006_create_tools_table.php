<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('category')->nullable(); // api, automation, ai, design
            $table->string('icon')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();

            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
