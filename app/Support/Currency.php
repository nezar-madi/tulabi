<?php

namespace App\Support;

/**
 * Tulabi works in Libyan dinars only. Each payment still stores its own currency_code
 * (as in rqmana), so a second currency would not need old rows rewritten.
 */
class Currency
{
    public const CODE = 'LYD';

    /** Fractional digits of the dinar (1 LYD = 1000 dirham). */
    public const DECIMALS = 3;

    public const SYMBOL = 'د.ل';
}
