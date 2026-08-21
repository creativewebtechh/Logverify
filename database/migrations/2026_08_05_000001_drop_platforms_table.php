<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platforms')) {
            Schema::dropIfExists('platforms');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('platforms')) {
            Schema::create('platforms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('icon')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }
};
