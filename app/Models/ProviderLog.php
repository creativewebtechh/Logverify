<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'order_id',
        'channel',
        'type',
        'status',
        'response_time_ms',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'response_time_ms' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
