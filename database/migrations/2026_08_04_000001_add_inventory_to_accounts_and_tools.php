<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(1)->after('price');
            $table->boolean('featured')->default(false)->after('stock');
            $table->string('image')->nullable()->after('featured');
            $table->json('credentials')->nullable()->after('meta');
            $table->index('featured');
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(1)->after('price');
            $table->boolean('featured')->default(false)->after('stock');
            $table->string('image')->nullable()->after('featured');
            $table->string('download_url')->nullable()->after('icon');
            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['featured']);
            $table->dropColumn(['stock', 'featured', 'image', 'credentials']);
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->dropIndex(['featured']);
            $table->dropColumn(['stock', 'featured', 'image', 'download_url']);
        });
    }
};
