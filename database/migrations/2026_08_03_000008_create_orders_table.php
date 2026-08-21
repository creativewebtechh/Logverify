<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('orderable'); // number, tool, service
            $table->string('title');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->string('status')->default('pending'); // pending, paid, processing, completed, failed, refunded
            $table->string('reference')->unique()->nullable();
            $table->string('payment_method')->default('wallet');
            $table->string('gateway')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
