<?php

namespace App\Models;

use App\Services\Providers\Contracts\ProviderConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Provider extends Model implements ProviderConfig
{
    use HasFactory;

    public const CHANNEL_NUMBERS = 'numbers';

    public const CHANNEL_BOOST = 'boost';

    protected $fillable = [
        'channel',
        'name',
        'driver',
        'base_url',
        'api_key',
        'order_endpoint',
        'status_endpoint',
        'balance_endpoint',
        'services_endpoint',
        'priority',
        'active',
        'balance',
        'total_services',
        'last_synced_at',
        'last_used_at',
        'currency',
        'logo',
        'notes',
        'health_status',
        'response_time_ms',
        'success_rate',
        'total_calls',
        'total_failures',
        'last_error',
        'last_health_check_at',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'active' => 'boolean',
            'priority' => 'integer',
            'balance' => 'string',
            'total_services' => 'integer',
            'last_synced_at' => 'datetime',
            'last_used_at' => 'datetime',
            'response_time_ms' => 'integer',
            'success_rate' => 'decimal:2',
            'total_calls' => 'integer',
            'total_failures' => 'integer',
            'last_health_check_at' => 'datetime',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProviderLog::class);
    }

    public function serviceProviders(): HasMany
    {
        return $this->hasMany(ServiceProvider::class);
    }

    public function services(): HasManyThrough
    {
        return $this->hasManyThrough(Service::class, ServiceProvider::class, 'provider_id', 'id');
    }

    public function numberServices(): HasMany
    {
        return $this->hasMany(NumberService::class);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // --- ProviderConfig -----------------------------------------------------

    public function channel(): string
    {
        return (string) $this->channel;
    }

    public function name(): string
    {
        return (string) $this->name;
    }

    public function driver(): string
    {
        return (string) $this->driver;
    }

    public function apiKey(): string
    {
        return (string) $this->api_key;
    }

    public function baseUrl(): string
    {
        return (string) $this->base_url;
    }

    public function orderEndpoint(): string
    {
        return (string) $this->order_endpoint;
    }

    public function statusEndpoint(): string
    {
        return (string) $this->status_endpoint;
    }

    public function balanceEndpoint(): string
    {
        return (string) $this->balance_endpoint;
    }

    public function servicesEndpoint(): string
    {
        return (string) $this->services_endpoint;
    }

    public function providerId(): ?int
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    // --- Helpers --------------------------------------------------------------

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '' && $this->baseUrl() !== '';
    }

    public function getMaskedKeyAttribute(): ?string
    {
        $key = $this->apiKey();

        return $key === '' ? null : '••••••••'.substr($key, -4);
    }

    // --- Health --------------------------------------------------------------

    public function isHealthy(): bool
    {
        return $this->health_status === 'healthy';
    }

    public function isAvailable(): bool
    {
        return $this->active && $this->health_status !== 'unhealthy';
    }

    public function scopeHealthy($query)
    {
        return $query->where('health_status', '!=', 'unhealthy');
    }

    public function recordCall(bool $success): void
    {
        $this->total_calls = (int) $this->total_calls + 1;

        if (! $success) {
            $this->total_failures = (int) $this->total_failures + 1;
        }

        $successRate = $this->total_calls > 0
            ? round((($this->total_calls - (int) $this->total_failures) / $this->total_calls) * 100, 2)
            : 100;

        $this->success_rate = $successRate;
        $this->saveQuietly();
    }
}
