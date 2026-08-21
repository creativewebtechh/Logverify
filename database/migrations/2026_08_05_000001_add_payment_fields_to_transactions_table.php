<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('fee', 14, 2)->nullable()->after('amount');
            $table->string('payment_method')->nullable()->after('gateway');
            $table->string('payment_status')->nullable()->after('status');
            $table->json('gateway_response')->nullable()->after('meta');
            $table->json('webhook_payload')->nullable()->after('gateway_response');
            $table->string('ip_address', 45)->nullable()->after('webhook_payload');
            $table->string('device', 255)->nullable()->after('ip_address');

            $table->index('payment_status');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['payment_method']);

            $table->dropColumn([
                'fee',
                'payment_method',
                'payment_status',
                'gateway_response',
                'webhook_payload',
                'ip_address',
                'device',
            ]);
        });
    }
};
