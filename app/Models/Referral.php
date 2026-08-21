<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_CLAIMED = 'claimed';

    protected $fillable = [
        'user_id',
        'referred_user_id',
        'bonus_amount',
        'status',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'bonus_amount' => 'decimal:2',
            'claimed_at' => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
