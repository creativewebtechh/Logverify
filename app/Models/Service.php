<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Service extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'platform',
        'price_per_unit',
        'cost_per_unit',
        'min_qty',
        'max_qty',
        'avg_time',
        'provider_service_id',
        'icon',
        'status',
        'category_id',
        'image',
        'tags',
        'featured',
        'recommended',
        'best_seller',
        'popular',
        'pinned',
        'hidden',
        'popularity_score',
        'sort_order',
        'refill',
        'cancel',
        'dripfeed',
        'markup_percent',
        'min_profit',
        'max_profit',
        'seo_title',
        'seo_description',
        'price_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'price_per_unit' => 'decimal:4',
            'cost_per_unit' => 'decimal:4',
            'tags' => 'array',
            'featured' => 'boolean',
            'recommended' => 'boolean',
            'best_seller' => 'boolean',
            'popular' => 'boolean',
            'pinned' => 'boolean',
            'hidden' => 'boolean',
            'popularity_score' => 'integer',
            'sort_order' => 'integer',
            'refill' => 'boolean',
            'cancel' => 'boolean',
            'dripfeed' => 'boolean',
            'markup_percent' => 'decimal:2',
            'min_profit' => 'decimal:4',
            'max_profit' => 'decimal:4',
            'price_updated_at' => 'datetime',
        ];
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function serviceProviders(): HasMany
    {
        return $this->hasMany(ServiceProvider::class);
    }

    public function providers()
    {
        return $this->hasManyThrough(Provider::class, ServiceProvider::class, 'service_id', 'id')
            ->where('service_providers.status', ServiceProvider::STATUS_ACTIVE)
            ->where('providers.active', true);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('hidden', false);
    }

    public function scopeForCatalog(Builder $query): Builder
    {
        return $query->visible()->with('category');
    }

    public function priceFor(int $quantity): float
    {
        return round((float) $this->price_per_unit * $quantity, 2);
    }

    public function baseCost(): ?float
    {
        $cheapest = $this->serviceProviders()
            ->active()
            ->orderBy('rate')
            ->first();

        if ($cheapest) {
            return (float) $cheapest->rate;
        }

        return $this->cost_per_unit !== null ? (float) $this->cost_per_unit : null;
    }

    public function marginPercent(): ?float
    {
        if ($this->cost_per_unit === null || (float) $this->cost_per_unit <= 0) {
            return null;
        }

        if ((float) $this->price_per_unit <= 0) {
            return null;
        }

        return round(((float) $this->price_per_unit - (float) $this->cost_per_unit) / (float) $this->cost_per_unit * 100, 2);
    }

    public function cheapestProvider(): ?ServiceProvider
    {
        return $this->serviceProviders()
            ->active()
            ->with('provider')
            ->get()
            ->filter(fn (ServiceProvider $sp) => $sp->provider && $sp->provider->active)
            ->sortBy('rate')
            ->first();
    }

    public function fastestProvider(): ?ServiceProvider
    {
        return $this->serviceProviders()
            ->active()
            ->with('provider')
            ->get()
            ->filter(fn (ServiceProvider $sp) => $sp->provider && $sp->provider->active)
            ->sortBy('avg_time')
            ->first();
    }

    public function isFavoritedBy(int $userId): bool
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }
}
