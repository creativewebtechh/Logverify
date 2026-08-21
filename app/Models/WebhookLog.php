<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail for every webhook delivery received from a payment gateway.
 *
 * Rows are written on receipt (before any processing) so failed webhooks and
 * invalid signatures are never lost, and marked processed/failed once the
 * queued worker has settled the transaction.
 */
class WebhookLog extends Model
{
    use HasFactory;

    public const STATUS_RECEIVED = 'received';

    public const STATUS_INVALID_SIGNATURE = 'invalid_signature';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'gateway',
        'event',
        'reference',
        'status',
        'response_status',
        'source_ip',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_status' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function scopeForGateway($query, ?string $gateway)
    {
        return $query->when($gateway, fn ($q) => $q->where('gateway', $gateway));
    }

    public function scopeWithStatus($query, ?string $status)
    {
        return $query->when($status, fn ($q) => $q->where('status', $status));
    }

    public function scopeSearch($query, ?string $term)
    {
        return $query->when(filled($term), fn ($q) => $q->where(function ($q) use ($term) {
            $q->where('reference', 'like', "%{$term}%")
                ->orWhere('event', 'like', "%{$term}%");
        }));
    }
}
