<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('platform')
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('image')->nullable()->after('icon');
            $table->json('tags')->nullable()->after('image');
            $table->boolean('featured')->default(false)->after('tags');
            $table->boolean('recommended')->default(false)->after('featured');
            $table->boolean('best_seller')->default(false)->after('recommended');
            $table->boolean('popular')->default(false)->after('best_seller');
            $table->boolean('pinned')->default(false)->after('popular');
            $table->boolean('hidden')->default(false)->after('pinned');
            $table->unsignedInteger('popularity_score')->default(0)->after('hidden');
            $table->unsignedInteger('sort_order')->default(0)->after('popularity_score');
            $table->boolean('refill')->default(false)->after('avg_time');
            $table->boolean('cancel')->default(false)->after('refill');
            $table->boolean('dripfeed')->default(false)->after('cancel');
            $table->decimal('markup_percent', 8, 2)->nullable()->after('cost_per_unit');
            $table->decimal('min_profit', 14, 4)->nullable()->after('markup_percent');
            $table->decimal('max_profit', 14, 4)->nullable()->after('min_profit');
            $table->string('seo_title')->nullable()->after('max_profit');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->timestamp('price_updated_at')->nullable()->after('seo_description');

            $table->index('popularity_score');
            $table->index('hidden');
            $table->index(['status', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['popularity_score']);
            $table->dropIndex(['hidden']);
            $table->dropIndex(['status', 'category_id']);
            $table->dropColumn([
                'category_id', 'image', 'tags', 'featured', 'recommended', 'best_seller',
                'popular', 'pinned', 'hidden', 'popularity_score', 'sort_order',
                'refill', 'cancel', 'dripfeed', 'markup_percent', 'min_profit',
                'max_profit', 'seo_title', 'seo_description', 'price_updated_at',
            ]);
        });
    }
};
