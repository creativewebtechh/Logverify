<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('currency', 3)->default('NGN')->after('base_url');
            $table->string('logo')->nullable()->after('currency');
            $table->text('notes')->nullable()->after('logo');
            $table->string('health_status')->default('unknown')->after('active'); // healthy, degraded, unhealthy, unknown
            $table->unsignedInteger('response_time_ms')->nullable()->after('health_status');
            $table->decimal('success_rate', 5, 2)->nullable()->after('response_time_ms');
            $table->unsignedInteger('total_calls')->default(0)->after('success_rate');
            $table->unsignedInteger('total_failures')->default(0)->after('total_calls');
            $table->text('last_error')->nullable()->after('total_failures');
            $table->timestamp('last_health_check_at')->nullable()->after('last_error');

            $table->index('health_status');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropIndex(['health_status']);
            $table->dropColumn([
                'currency', 'logo', 'notes', 'health_status', 'response_time_ms',
                'success_rate', 'total_calls', 'total_failures', 'last_error',
                'last_health_check_at',
            ]);
        });
    }
};
