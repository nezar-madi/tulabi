<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Libya's local calendar.
 *
 * Timestamps stay in UTC in the database and in PHP. Anything that asks "what day is it"
 * or "which month is this" must ask here instead of calling now()->toDateString(), which
 * answers in UTC and is a day off around local midnight. Carried over from rqmana's
 * CenterClock, with one fixed time zone instead of one per center.
 */
class TulabiClock
{
    /**
     * The earliest date a payment may carry. Money dates are stored in TIMESTAMP
     * columns, which cannot hold anything before 1970.
     */
    public const EARLIEST_MONEY_DATE = '2000-01-01';

    public static function timezone(): string
    {
        $timezone = config('tulabi.timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'Africa/Tripoli';
    }

    /** The current moment in Libya. */
    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::timezone());
    }

    /** Today's date in Libya (Y-m-d). */
    public static function today(): string
    {
        return self::now()->toDateString();
    }

    /** Local midnight at the start of a date, as a UTC timestamp. */
    public static function startOfDayUtc(string $date): Carbon
    {
        return Carbon::parse($date, self::timezone())->startOfDay()->utc();
    }

    /** The last second of a date, as a UTC timestamp. */
    public static function endOfDayUtc(string $date): Carbon
    {
        return Carbon::parse($date, self::timezone())->endOfDay()->utc();
    }

    /**
     * The current local month as a UTC range.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function monthRangeUtc(): array
    {
        $now = self::now();

        return [
            Carbon::instance($now->startOfMonth())->utc(),
            Carbon::instance($now->endOfMonth())->utc(),
        ];
    }

    /**
     * A typed date (Y-m-d, no time) means that day in Libya, so store it as the UTC
     * moment of local midnight. Anything with a time is left to Carbon.
     */
    public static function storeDate(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return self::startOfDayUtc($value);
        }

        return $value;
    }
}
