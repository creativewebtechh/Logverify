<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory;

    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_CATEGORY = 'category';

    public const SCOPE_SERVICE = 'service';

    public const SCOPE_PROVIDER = 'provider';

    protected $fillable = [
        'name',
        'scope',
        'service_id',
        'category_id',
        'provider_id',
        'markup_percent',
        'fixed_profit',
        'min_profit',
        'max_profit',
        'currency',
        'priority',
        'enabled',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'markup_percent' => 'decimal:2',
            'fixed_profit' => 'decimal:4',
            'min_profit' => 'decimal:4',
            'max_profit' => 'decimal:4',
            'priority' => 'integer',
            'enabled' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function isApplicableNow(): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
