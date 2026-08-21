<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'data',
        'action_url',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function unreadCountFor(int $userId): int
    {
        return Cache::remember(
            self::unreadCacheKey($userId),
            60,
            fn () => static::forUser($userId)->unread()->count()
        );
    }

    private static function unreadCacheKey(int $userId): string
    {
        return 'notifications.unread.'.$userId;
    }

    public static function forgetUnreadCache(int $userId): void
    {
        Cache::forget(self::unreadCacheKey($userId));
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
            self::forgetUnreadCache($this->user_id);
        }
    }

    public static function notify(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null, array $data = []): self
    {
        self::forgetUnreadCache($userId);

        return static::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }
}
