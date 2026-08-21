<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // referrer
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete(); // person who joined via link
            $table->decimal('bonus_amount', 14, 2)->default(0);
            $table->string('status')->default('pending'); // pending, ready, claimed
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'referred_user_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
