<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('provider_service_id');
            $table->decimal('rate', 14, 4);
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->default(100000);
            $table->string('avg_time')->nullable();
            $table->boolean('refill')->default(false);
            $table->boolean('cancel')->default(false);
            $table->boolean('dripfeed')->default(false);
            $table->string('status')->default('active'); // active, inactive
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'provider_service_id']);
            $table->unique(['provider_id', 'service_id']);
            $table->index(['service_id', 'status']);
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope')->default('global'); // global, category, service, provider
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->cascadeOnDelete();
            $table->decimal('markup_percent', 8, 2)->nullable();
            $table->decimal('fixed_profit', 14, 4)->nullable();
            $table->decimal('min_profit', 14, 4)->nullable();
            $table->decimal('max_profit', 14, 4)->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['enabled', 'scope']);
        });

        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->decimal('old_price', 14, 4)->nullable();
            $table->decimal('new_price', 14, 4);
            $table->decimal('cost', 14, 4)->nullable();
            $table->string('reason')->nullable();
            $table->string('changed_by')->default('manual'); // manual, sync, rule, bulk, rollback, system
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'created_at']);
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status');
            $table->string('note')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('price_history');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('service_providers');
    }
};
