<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'pending_balance',
        'total_credited',
        'total_debited',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'total_credited' => 'decimal:2',
            'total_debited' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
