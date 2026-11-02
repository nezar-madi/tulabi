<?php

namespace App\Support;

/**
 * Tulabi works in Libyan dinars only. Each payment still stores its own currency_code
 * (as in rqmana), so a second currency would not need old rows rewritten.
 *
 * Sums are done in whole dirhams (1 LYD = 1000 dirham) so no float rounding creeps in.
 */
class Currency
{
    public const CODE = 'LYD';

    /** Fractional digits of the dinar (1 LYD = 1000 dirham). */
    public const DECIMALS = 3;

    public const SYMBOL = 'د.ل';

    public static function toMillis(mixed $amount): int
    {
        return (int) round(((float) $amount) * 1000);
    }

    /** Dirhams back to a decimal string for the database: 12500 -> "12.500". */
    public static function fromMillis(int $millis): string
    {
        return number_format($millis / 1000, self::DECIMALS, '.', '');
    }

    /** For people: "300", "12.5" (no trailing zeros). */
    public static function format(mixed $amount): string
    {
        $text = number_format((float) $amount, self::DECIMALS, '.', '');

        return rtrim(rtrim($text, '0'), '.');
    }
}
