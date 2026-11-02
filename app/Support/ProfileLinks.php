<?php

namespace App\Support;

/**
 * The instructor's links (Facebook, Instagram, YouTube, a website...). Only http(s) URLs
 * are kept; the kind is read from the host for the icon.
 */
class ProfileLinks
{
    public const MAX = 5;

    private const KINDS = [
        'facebook' => ['facebook.com', 'fb.com', 'fb.me'],
        'instagram' => ['instagram.com'],
        'youtube' => ['youtube.com', 'youtu.be'],
        'tiktok' => ['tiktok.com'],
        'telegram' => ['t.me', 'telegram.me'],
        'x' => ['x.com', 'twitter.com'],
        'linkedin' => ['linkedin.com'],
    ];

    /**
     * @param  array<mixed>|null  $links
     * @return list<string>
     */
    public static function clean(?array $links): array
    {
        $clean = [];

        foreach ($links ?? [] as $link) {
            $url = trim((string) $link);

            if ($url !== '' && preg_match('#^https?://#i', $url) === 1 && ! in_array($url, $clean, true)) {
                $clean[] = $url;
            }
        }

        return array_slice($clean, 0, self::MAX);
    }

    /**
     * @param  list<string>|null  $links
     * @return list<array{url: string, kind: string}>
     */
    public static function present(?array $links): array
    {
        return array_map(fn (string $url) => ['url' => $url, 'kind' => self::kind($url)], self::clean($links));
    }

    public static function kind(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^(www\.|m\.)/', '', $host) ?? $host;

        foreach (self::KINDS as $kind => $hosts) {
            foreach ($hosts as $known) {
                if ($host === $known || str_ends_with($host, '.'.$known)) {
                    return $kind;
                }
            }
        }

        return 'website';
    }
}
