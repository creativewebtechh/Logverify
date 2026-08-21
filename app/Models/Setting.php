<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever('setting.'.$key, fn () => static::query()->where('key', $key)->value('value'));

        if ($value === null) {
            return $default;
        }

        return match (true) {
            is_numeric($value) => str_contains($value, '.') ? (float) $value : (int) $value,
            in_array($value, ['true', 'false'], true) => $value === 'true',
            default => $value,
        };
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);

        Cache::forget('setting.'.$key);
    }
}
