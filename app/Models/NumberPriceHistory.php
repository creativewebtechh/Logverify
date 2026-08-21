<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'number_price_history';

    public const CHANGED_MANUAL = 'manual';

    public const CHANGED_SYNC = 'sync';

    public const CHANGED_RULE = 'rule';

    public const CHANGED_BULK = 'bulk';

    public const CHANGED_ROLLBACK = 'rollback';

    public const CHANGED_SYSTEM = 'system';

    protected $fillable = [
        'number_service_id',
        'old_price',
        'new_price',
        'cost',
        'reason',
        'changed_by',
        'user_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:4',
            'new_price' => 'decimal:4',
            'cost' => 'decimal:4',
            'meta' => 'array',
        ];
    }

    public function numberService(): BelongsTo
    {
        return $this->belongsTo(NumberService::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
