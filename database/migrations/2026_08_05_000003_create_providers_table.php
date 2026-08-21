<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('channel'); // numbers, boost
            $table->string('name');
            $table->string('driver'); // generic, grizzly, smmpanel
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable(); // encrypted via model cast
            $table->string('order_endpoint')->nullable();
            $table->string('status_endpoint')->nullable();
            $table->string('balance_endpoint')->nullable();
            $table->string('services_endpoint')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('active')->default(true);
            $table->string('balance')->nullable();
            $table->unsignedInteger('total_services')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'active', 'priority']);
        });

        $this->importLegacySettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }

    /**
     * The encrypted model cast decrypts once on read. The legacy settings store
     * may hold either an already-encrypted payload (modern installs) or a raw
     * plaintext key (very old installs). Normalize both so the cast round-trips.
     */
    private function normalizeApiKey(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            decrypt($value);

            return $value;
        } catch (Throwable) {
            return encrypt($value);
        }
    }

    /**
     * Upgrade path: carry over credentials previously stored in the legacy
     * settings table (provider.numbers.* / provider.boost.*) into real
     * provider rows so existing installs keep working after the switch.
     */
    private function importLegacySettings(): void
    {
        foreach (['numbers', 'boost'] as $channel) {
            $baseUrl = DB::table('settings')->where('key', "provider.{$channel}.base_url")->value('value');
            $apiKey = DB::table('settings')->where('key', "provider.{$channel}.api_key")->value('value');

            $alreadyMigrated = DB::table('providers')->where('channel', $channel)->exists();
            $hasConfig = filled($baseUrl) || filled($apiKey);

            if ($alreadyMigrated || ! $hasConfig) {
                continue;
            }

            $apiKey = $this->normalizeApiKey($apiKey);

            DB::table('providers')->insert([
                'channel' => $channel,
                'name' => ucfirst($channel).' provider',
                'driver' => DB::table('settings')->where('key', "provider.{$channel}.driver")->value('value') ?? 'generic',
                'base_url' => $baseUrl,
                'api_key' => $apiKey,
                'order_endpoint' => DB::table('settings')->where('key', "provider.{$channel}.order_endpoint")->value('value'),
                'status_endpoint' => DB::table('settings')->where('key', "provider.{$channel}.status_endpoint")->value('value'),
                'balance_endpoint' => DB::table('settings')->where('key', "provider.{$channel}.balance_endpoint")->value('value'),
                'services_endpoint' => DB::table('settings')->where('key', "provider.{$channel}.services_endpoint")->value('value'),
                'priority' => 0,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
