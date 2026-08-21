<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('cost_per_unit', 14, 4)->nullable()->after('price_per_unit');
            $table->string('avg_time')->nullable()->after('max_qty');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['cost_per_unit', 'avg_time']);
        });
    }
};
