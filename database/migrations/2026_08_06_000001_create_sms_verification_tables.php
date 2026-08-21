<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_services', function (Blueprint $table) {
            $table->id();
            $table->string('catalog_key')->unique();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('provider_service_id')->nullable();
            $table->string('provider_country_id')->nullable();
            $table->string('country_code', 4);
            $table->string('country_name');
            $table->string('name');
            $table->string('slug');
            $table->string('category')->default('sms');
            $table->string('icon')->nullable();
            $table->decimal('price', 14, 4)->default(0);
            $table->decimal('cost', 14, 4)->nullable();
            $table->decimal('markup_percent', 8, 2)->nullable();
            $table->decimal('min_profit', 14, 4)->nullable();
            $table->decimal('max_profit', 14, 4)->nullable();
            $table->string('eta')->nullable();
            $table->unsignedInteger('eta_seconds')->nullable();
            $table->integer('stock')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('popular')->default(false);
            $table->boolean('hidden')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('popularity_score')->default(0);
            $table->string('status')->default('active');
            $table->json('meta')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('price_updated_at')->nullable();
            $table->timestamps();

            $table->index(['country_code', 'status']);
            $table->index(['status', 'featured']);
            $table->index(['status', 'popular']);
            $table->index(['provider_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('channel')->nullable()->after('status');
            $table->string('provider_reference')->nullable()->after('channel');
            $table->foreignId('provider_id')->nullable()->after('provider_reference')->constrained('providers')->nullOnDelete();
            $table->string('phone_number')->nullable()->after('provider_id');
            $table->string('sms_status')->nullable()->after('phone_number');
            $table->string('sms_code')->nullable()->after('sms_status');
            $table->timestamp('sms_code_at')->nullable()->after('sms_code');
            $table->timestamp('expires_at')->nullable()->after('sms_code_at');
            $table->timestamp('refunded_at')->nullable()->after('expires_at');

            $table->index('channel');
            $table->index('provider_reference');
            $table->index('sms_status');
            $table->index('expires_at');
        });

        DB::table('orders')->update([
            'channel' => DB::raw("CASE
                WHEN orderable_type = 'App\\Models\\Number' THEN 'numbers'
                WHEN orderable_type = 'App\\Models\\NumberService' THEN 'numbers'
                WHEN orderable_type = 'App\\Models\\Service' THEN 'boost'
                WHEN orderable_type = 'App\\Models\\Account' THEN 'accounts'
                WHEN orderable_type = 'App\\Models\\Tool' THEN 'tools'
                ELSE NULL
            END"),
        ]);

        Schema::table('favorites', function (Blueprint $table) {
            $table->foreignId('number_service_id')->nullable()->after('service_id')->constrained('number_services')->nullOnDelete();
        });

        Schema::table('provider_logs', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('provider_id')->constrained('orders')->nullOnDelete();
            $table->index('order_id');
        });

        Schema::create('number_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('number_service_id')->constrained('number_services')->cascadeOnDelete();
            $table->decimal('old_price', 14, 4)->nullable();
            $table->decimal('new_price', 14, 4);
            $table->decimal('cost', 14, 4)->nullable();
            $table->string('reason')->nullable();
            $table->string('changed_by')->default('manual');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['number_service_id', 'created_at']);
        });

        $this->importLegacyNumbers();
    }

    public function down(): void
    {
        Schema::dropIfExists('number_price_history');

        Schema::table('provider_logs', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropForeign(['number_service_id']);
            $table->dropColumn('number_service_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->dropIndex(['provider_reference']);
            $table->dropIndex(['sms_status']);
            $table->dropIndex(['expires_at']);
            $table->dropForeign(['provider_id']);
            $table->dropColumn(['channel', 'provider_reference', 'provider_id', 'phone_number', 'sms_status', 'sms_code', 'sms_code_at', 'expires_at', 'refunded_at']);
        });

        Schema::dropIfExists('number_services');
    }

    private function importLegacyNumbers(): void
    {
        $rows = DB::table('numbers')->get();

        if ($rows->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($rows as $row) {
            $countryCode = strtoupper((string) ($row->country ?? 'XX'));
            $countryCode = strlen($countryCode) > 4 ? substr($countryCode, 0, 4) : $countryCode;
            $category = (string) ($row->category ?? 'sms');
            $providerServiceId = (string) ($row->provider_service_id ?? '');
            $providerId = $row->provider_id !== null ? (int) $row->provider_id : null;
            $catalogKey = sprintf('%s:%s:%s', $providerId ?? 'manual', $countryCode, $providerServiceId !== '' ? $providerServiceId : $category);

            DB::table('number_services')->updateOrInsert(
                ['catalog_key' => $catalogKey],
                [
                    'catalog_key' => $catalogKey,
                    'provider_id' => $providerId,
                    'provider_service_id' => $providerServiceId !== '' ? $providerServiceId : null,
                    'country_code' => $countryCode,
                    'country_name' => ucwords(str_replace(['_', '-'], ' ', (string) $row->country)),
                    'name' => ucwords(str_replace(['_', '-'], ' ', $category)).' verification',
                    'slug' => Str::slug($category.'-'.$countryCode.'-'.$providerServiceId),
                    'category' => $category,
                    'price' => (float) ($row->price ?? 0),
                    'cost' => (float) ($row->price ?? 0),
                    'stock' => null,
                    'status' => $row->status === 'available' ? 'active' : 'inactive',
                    'meta' => json_encode(['legacy_number' => $row->masked_number ?? null, 'legacy_number_id' => $row->id]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
};
