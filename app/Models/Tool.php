<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'category',
        'icon',
        'status',
        'stock',
        'featured',
        'image',
        'download_url',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'featured' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }
}
