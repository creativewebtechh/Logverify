<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceProvider extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'service_providers';

    protected $fillable = [
        'provider_id',
        'service_id',
        'provider_service_id',
        'rate',
        'min_qty',
        'max_qty',
        'avg_time',
        'refill',
        'cancel',
        'dripfeed',
        'status',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'min_qty' => 'integer',
            'max_qty' => 'integer',
            'refill' => 'boolean',
            'cancel' => 'boolean',
            'dripfeed' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
