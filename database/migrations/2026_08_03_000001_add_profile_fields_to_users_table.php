<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('name');
            $table->string('referral_code', 16)->unique()->nullable()->after('remember_token');
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            $table->enum('role', ['customer', 'admin'])->default('customer')->after('referred_by');
            $table->boolean('is_verified')->default(false)->after('role');
            $table->boolean('status')->default(true)->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by');
            $table->dropColumn(['avatar', 'referral_code', 'role', 'is_verified', 'status']);
        });
    }
};
