<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NumberService extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'catalog_key',
        'provider_id',
        'provider_service_id',
        'provider_country_id',
        'country_code',
        'country_name',
        'name',
        'slug',
        'category',
        'icon',
        'price',
        'cost',
        'markup_percent',
        'min_profit',
        'max_profit',
        'eta',
        'eta_seconds',
        'stock',
        'featured',
        'popular',
        'hidden',
        'sort_order',
        'popularity_score',
        'status',
        'meta',
        'last_synced_at',
        'price_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'cost' => 'decimal:4',
            'markup_percent' => 'decimal:2',
            'min_profit' => 'decimal:4',
            'max_profit' => 'decimal:4',
            'eta_seconds' => 'integer',
            'stock' => 'integer',
            'featured' => 'boolean',
            'popular' => 'boolean',
            'hidden' => 'boolean',
            'sort_order' => 'integer',
            'popularity_score' => 'integer',
            'meta' => 'array',
            'last_synced_at' => 'datetime',
            'price_updated_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(NumberPriceHistory::class)->latest('id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeVisible($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)->where('hidden', false);
    }

    public function scopeForCatalog($query)
    {
        return $query->visible()->orderBy('sort_order')->orderBy('name');
    }

    public static function makeCatalogKey(?int $providerId, string $countryCode, string $providerServiceId = '', string $name = ''): string
    {
        $key = (string) $providerId;

        if ($key === '') {
            $key = 'manual';
        }

        return sprintf('%s:%s:%s', $key, strtoupper($countryCode), $providerServiceId !== '' ? $providerServiceId : $name);
    }

    public function baseCost(): ?float
    {
        $cost = $this->cost;

        return $cost !== null ? (float) $cost : null;
    }

    public function isFavoritedBy(?User $user): bool
    {
        return $user !== null && $this->favorites()->where('user_id', $user->id)->exists();
    }

    public function marginPercent(): ?float
    {
        $cost = $this->baseCost();

        if ($cost === null || $cost <= 0) {
            return null;
        }

        return round((((float) $this->price - $cost) / $cost) * 100, 2);
    }

    public function displayName(): string
    {
        return $this->name.' ('.$this->country_name.')';
    }

    public function flag(): string
    {
        $regional = $this->flagEmoji($this->country_code);

        if ($regional !== '') {
            return $regional;
        }

        return '🏳️';
    }

    public static function flagEmoji(string $countryCode): string
    {
        $code = strtoupper(substr($countryCode, 0, 2));

        if ($code === '' || preg_match('/^[A-Z]{2}$/', $code) !== 1) {
            return '';
        }

        $result = '';

        foreach (str_split($code) as $char) {
            $result .= mb_chr(127397 + ord($char));
        }

        return $result;
    }
}
