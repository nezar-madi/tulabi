<?php

namespace App\Support;

class SafeHtml
{
    /**
     * Allow a small set of formatting tags for class descriptions.
     * All attributes are stripped except a vetted href on <a>.
     */
    public static function description(?string $html): string
    {
        $html = (string) $html;

        if ($html === '') {
            return '';
        }

        // Decode entities so obfuscated handlers/attributes become filterable.
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $allowed = '<p><br><strong><b><em><i><u><s><strike><h2><h3><ul><ol><li><a><blockquote><code><pre>';
        $cleaned = strip_tags($html, $allowed);

        $cleaned = preg_replace_callback(
            '/<(\/?)([a-z0-9]+)([^>]*)>/iu',
            static function (array $matches): string {
                $isClosing = ($matches[1] ?? '') === '/';
                $tag = strtolower($matches[2] ?? '');
                $attrs = $matches[3] ?? '';

                if ($tag === '') {
                    return '';
                }

                if ($isClosing) {
                    return $tag === 'br' ? '' : '</'.$tag.'>';
                }

                if ($tag === 'br') {
                    return '<br>';
                }

                if ($tag === 'a') {
                    $href = null;

                    if (preg_match('/\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/iu', $attrs, $hrefMatch)) {
                        $href = $hrefMatch[1] ?? $hrefMatch[2] ?? $hrefMatch[3] ?? null;
                    }

                    $href = self::safeUrl(is_string($href) ? $href : null);

                    if ($href === null) {
                        return '<a>';
                    }

                    return '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer nofollow">';
                }

                return '<'.$tag.'>';
            },
            $cleaned
        ) ?? $cleaned;

        return trim($cleaned);
    }

    public static function hasText(?string $html): bool
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', '', $text) ?? '';

        return $text !== '';
    }

    private static function safeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '') {
            return null;
        }

        // Block javascript: and other dangerous schemes (also after whitespace tricks).
        if (preg_match('/^\s*javascript\s*:/iu', $url) === 1) {
            return null;
        }

        if (preg_match('/^\s*data\s*:/iu', $url) === 1) {
            return null;
        }

        if (preg_match('/^\s*vbscript\s*:/iu', $url) === 1) {
            return null;
        }

        // Allow absolute http(s)/mailto, same-origin paths, and in-page hashes.
        // Reject protocol-relative URLs (//evil.example).
        // Browsers read "/\host" like "//host", so a backslash after the slash is refused too.
        if (preg_match('/^(https?:\/\/|mailto:|\/(?![\/\\\\])|#)/iu', $url) !== 1) {
            return null;
        }

        return $url;
    }
}
