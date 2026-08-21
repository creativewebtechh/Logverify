<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('channel'); // numbers, boost
            $table->string('type'); // order, balance, services, health
            $table->string('status'); // success, failed
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'created_at']);
            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_logs');
    }
};
