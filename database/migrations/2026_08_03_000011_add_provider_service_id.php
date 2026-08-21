<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('numbers', function (Blueprint $table) {
            $table->string('provider_service_id', 100)->nullable()->after('price');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('provider_service_id', 100)->nullable()->after('max_qty');
        });
    }

    public function down(): void
    {
        Schema::table('numbers', function (Blueprint $table) {
            $table->dropColumn('provider_service_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('provider_service_id');
        });
    }
};
