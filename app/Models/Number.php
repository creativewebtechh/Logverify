<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Number extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'category',
        'number',
        'masked_number',
        'price',
        'provider_service_id',
        'provider_id',
        'currency',
        'status',
        'provider',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
