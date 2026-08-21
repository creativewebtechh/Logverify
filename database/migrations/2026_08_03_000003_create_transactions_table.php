<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // deposit, withdrawal, purchase, referral_commission, refund
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->string('status')->default('pending'); // pending, success, failed
            $table->string('reference')->unique()->nullable();
            $table->string('gateway')->nullable();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
