<?php

namespace App\Support;

/**
 * The instructor's «نبذة» as sections: the first one is «من أنا», and others may be added
 * («مؤهلاتي», «الخبرة العملية»...). A section is {title, body}; the body is HTML that has
 * been through SafeHtml::description().
 */
class AboutSections
{
    public const DEFAULT_TITLE = 'من أنا';

    public const MAX = 8;

    public const TITLE_MAX = 60;

    public const BODY_MAX = 10000;

    /** Titles offered as one-click additions on the profile page. */
    public const SUGGESTIONS = ['مؤهلاتي', 'الخبرة العملية', 'الشهادات', 'مجالات التدريس', 'إنجازاتي'];

    /**
     * Sections ready to save: cleaned, the first one always there (it may have no text yet).
     *
     * @param  array<int, array{title?: mixed, body?: mixed}>  $sections
     * @return list<array{title: string, body: string}>
     */
    public static function clean(array $sections): array
    {
        $clean = [];

        foreach (array_values($sections) as $index => $section) {
            $title = trim(preg_replace('/\s+/u', ' ', (string) ($section['title'] ?? '')) ?? '');
            $body = SafeHtml::description((string) ($section['body'] ?? ''));
            $body = SafeHtml::hasText($body) ? $body : '';

            if ($index > 0 && $body === '') {
                continue;
            }

            $clean[] = ['title' => $title !== '' ? $title : self::DEFAULT_TITLE, 'body' => $body];
        }

        return $clean === [] ? [['title' => self::DEFAULT_TITLE, 'body' => '']] : array_slice($clean, 0, self::MAX);
    }

    /**
     * The sections an instructor has: saved ones, or their old plain bio as «من أنا».
     *
     * @param  array<int, array{title: string, body: string}>|null  $saved
     * @return list<array{title: string, body: string}>
     */
    public static function from(?array $saved, ?string $bio): array
    {
        if ($saved !== null && $saved !== []) {
            return array_values($saved);
        }

        $bio = trim((string) $bio);

        return [[
            'title' => self::DEFAULT_TITLE,
            'body' => $bio === '' ? '' : '<p>'.nl2br(e($bio), false).'</p>',
        ]];
    }

    /** Sections with something to show (a visitor never sees an empty one). */
    public static function visible(array $sections): array
    {
        return array_values(array_filter($sections, fn (array $section) => SafeHtml::hasText($section['body'])));
    }

    /**
     * The first section as plain text: kept in `bio`, which feeds the page's lead line and
     * description. Paragraphs become lines.
     *
     * @param  list<array{title: string, body: string}>  $sections
     */
    public static function plain(array $sections): ?string
    {
        $html = (string) ($sections[0]['body'] ?? '');
        $text = preg_replace('/<\/(p|li|h2|h3)>|<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = trim(preg_replace("/\n{2,}/", "\n", html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5)) ?? '');

        return $text === '' ? null : $text;
    }
}
