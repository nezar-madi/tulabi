<?php

namespace App\Support;

/**
 * The instructor's page address: tulabi.ly/{slug}. Pages share the top level with the
 * site's own paths, so every first path segment the site uses (or may use) is reserved.
 */
class InstructorSlug
{
    public const MIN_LENGTH = 3;

    public const MAX_LENGTH = 40;

    /** Lowercase English letters, digits and single dashes; starts with a letter, no dash at the end. */
    public const PATTERN = '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/';

    /**
     * Never usable as a slug. Add every new top-level path here (routes/web.php checks it).
     */
    public const RESERVED = [
        // The site's own paths.
        'account', 'admin', 'build', 'classes', 'forgot-password', 'join', 'login', 'logout',
        'register', 'reset', 'storage', 'teach', 'up',
        // Kept for later or because they would mislead.
        'about', 'api', 'app', 'assets', 'blog', 'contact', 'dashboard', 'help', 'home',
        'instructor', 'instructors', 'mail', 'pay', 'payment', 'payments', 'privacy',
        'profile', 'receipt', 'receipts', 'search', 'settings', 'static', 'student',
        'students', 'support', 'terms', 'tulabi', 'www',
        'sitemap', 'dpay', 'mypay', 'webhook', 'webhooks', 'status', 'security',
    ];

    /** The slug as typed, cleaned: trimmed and lowercased (nothing else is changed). */
    public static function normalize(?string $slug): string
    {
        return strtolower(trim((string) $slug));
    }

    public static function isReserved(string $slug): bool
    {
        return in_array($slug, self::RESERVED, true);
    }

    /** The route constraint for {slug}: the pattern without the reserved words. */
    public static function routePattern(): string
    {
        return '(?!(?:'.implode('|', array_map('preg_quote', self::RESERVED)).')$)[a-z][a-z0-9]*(?:-[a-z0-9]+)*';
    }
}
