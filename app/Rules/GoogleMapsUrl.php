<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A Google Maps link: the long form (google.com/maps..., maps.google.com) or the short links
 * the app shares (maps.app.goo.gl/..., goo.gl/maps/...). Only https; nothing else is accepted,
 * so the saved link can be put in a page as an address the visitor opens in Maps.
 */
class GoogleMapsUrl implements ValidationRule
{
    public static function isValid(?string $value): bool
    {
        $parts = parse_url(trim((string) $value));

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['port'])) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ($host === 'maps.app.goo.gl') {
            return strlen($path) > 1;
        }

        if ($host === 'goo.gl') {
            return str_starts_with($path, '/maps/') && strlen($path) > 6;
        }

        if (! preg_match('/^(www\.|maps\.)?google\.(com|ly|com\.ly)$/', $host)) {
            return false;
        }

        return str_starts_with($host, 'maps.') || str_starts_with($path, '/maps');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value)) {
            $fail(__('classes.map_url_invalid'));
        }
    }
}
