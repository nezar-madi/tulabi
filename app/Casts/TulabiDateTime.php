<?php

namespace App\Casts;

use App\Support\TulabiClock;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A UTC timestamp that people usually enter as a plain date (a payment date). A bare
 * Y-m-d is read as that day in Libya, not as UTC midnight, so the date shown back is
 * the date that was typed. Copied from rqmana's CenterDateTime.
 *
 * @implements CastsAttributes<Carbon, mixed>
 */
class TulabiDateTime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value, 'UTC');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = TulabiClock::storeDate($value);
        $moment = $value instanceof \DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value);

        return $moment->utc()->format('Y-m-d H:i:s');
    }
}
