<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'title',
        'description',
        'price',
        'currency',
        'status',
        'provider',
        'provider_service_id',
        'stock',
        'featured',
        'image',
        'meta',
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'featured' => 'boolean',
            'meta' => 'array',
            'credentials' => 'array',
        ];
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }
}
