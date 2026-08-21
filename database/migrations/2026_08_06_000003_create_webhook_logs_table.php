<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');
            $table->string('event')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->default('received');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->string('source_ip', 45)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('gateway');
            $table->index('reference');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
