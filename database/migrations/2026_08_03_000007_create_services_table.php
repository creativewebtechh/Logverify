<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type'); // boost, followers, likes, views, comments
            $table->string('platform'); // instagram, tiktok, facebook, youtube, twitter
            $table->decimal('price_per_unit', 14, 4);
            $table->unsignedInteger('min_qty')->default(10);
            $table->unsignedInteger('max_qty')->default(10000);
            $table->string('icon')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();

            $table->index(['status', 'platform']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
