<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('platform')->nullable(); // instagram, tiktok, ..., null = cross-platform
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();

            $table->index(['status', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
