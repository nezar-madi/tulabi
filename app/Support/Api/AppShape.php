<?php

namespace App\Support\Api;

use Illuminate\Support\Carbon;

/**
 * Turns the arrays the website's presenters make into what the app needs: every link a full
 * address (images are stored as "/storage/..." paths, fine in a browser, not in an app), dates
 * as text, and the website-only links (forms that need a browser session) left out.
 */
class AppShape
{
    /** Keys that only make sense in a browser session. */
    private const WEB_ONLY = ['request_url', 'request_login_url'];

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function of(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array($key, self::WEB_ONLY, true)) {
                continue;
            }

            $out[$key] = match (true) {
                is_array($value) => self::of($value),
                $value instanceof Carbon => $value->toDateString(),
                is_string($key) && str_ends_with($key, '_url') && is_string($value) && str_starts_with($value, '/') => url($value),
                default => $value,
            };
        }

        return $out;
    }

    /**
     * @param  iterable<array<array-key, mixed>>  $items
     * @return list<array<array-key, mixed>>
     */
    public static function list(iterable $items): array
    {
        $out = [];

        foreach ($items as $item) {
            $out[] = self::of($item);
        }

        return $out;
    }
}
