<?php

namespace App\Support;

class Money
{
    public static function symbol(): string
    {
        return (string) config('app.currency_symbol');
    }

    public static function format(float|string|int|null $amount, int $decimals = 2): string
    {
        return static::symbol().number_format((float) $amount, $decimals);
    }
}
