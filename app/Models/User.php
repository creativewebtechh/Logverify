<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'password',
        'transaction_pin',
        'avatar',
        'referral_code',
        'referred_by',
        'role',
        'is_verified',
        'status',
    ];

    protected $hidden = [
        'password',
        'transaction_pin',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'transaction_pin' => 'hashed',
            'is_verified' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'user_id');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(Referral::class, 'referred_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasPin(): bool
    {
        return $this->transaction_pin !== null;
    }

    public function getReferralLink(): string
    {
        return route('register', ['ref' => $this->referral_code]);
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if ($user->referral_code === null) {
                $user->referral_code = self::generateReferralCode();
                $user->saveQuietly();
            }
        });
    }

    private static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(6)), 0, 8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }
}
