<?php

namespace App\Support;

/**
 * What a program and a class say about themselves, in one shape (as on Udemy): a description,
 * then three bullet lists. Plain text only: the description is shown with its line breaks,
 * the lists as bullets, and nothing is ever rendered as HTML.
 */
class CourseContent
{
    /** The bullet lists, in the order they are shown. */
    public const LISTS = ['learn', 'requirements', 'audience'];

    public const DESCRIPTION_MAX = 5000;

    public const ITEMS_MAX = 15;

    public const ITEM_MAX = 200;

    /** A program's description is at least this long (a class's has no minimum). */
    public const PROGRAM_DESCRIPTION_MIN = 20;

    /** A point in a program's lists is at least this long (the lists themselves may stay empty). */
    public const PROGRAM_ITEM_MIN = 3;

    /**
     * Items ready to save: trimmed, empty ones dropped (the validation rules cap the count).
     *
     * @return list<string>
     */
    public static function cleanList(mixed $items): array
    {
        $clean = [];

        foreach ((array) $items as $item) {
            $text = trim(preg_replace('/\s+/u', ' ', is_string($item) ? $item : '') ?? '');

            if ($text !== '') {
                $clean[] = $text;
            }
        }

        return $clean;
    }

    /**
     * Plain description: normalised line breaks, no more than one blank line in a row. Anything that
     * is not text (a crafted request sending a list) becomes empty, so the validation rejects it.
     */
    public static function cleanDescription(mixed $text): string
    {
        if (! is_string($text)) {
            return '';
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? '');
    }

    /**
     * A one-line name or title: spaces collapsed, and the invisible characters that flip the
     * direction of the text after them removed (they could make a name display reversed).
     * Anything that is not text is left as it is for the validation to reject.
     */
    public static function cleanName(mixed $text): mixed
    {
        if (! is_string($text)) {
            return $text;
        }

        $text = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * A promotional video link: trimmed, empty becomes null; anything that is not text is left
     * for the validation to reject (it would otherwise be turned into "Array", a server error).
     */
    public static function cleanVideoUrl(mixed $url): mixed
    {
        if (! is_string($url)) {
            return $url;
        }

        $url = trim($url);

        return $url === '' ? null : $url;
    }

    /**
     * Validation rules for the shared content fields. A minimum length for the description and
     * for each point of the lists is optional (0 = none).
     *
     * @return array<string, mixed>
     */
    public static function rules(int $descriptionMin = 0, int $itemMin = 0): array
    {
        $rules = ['description' => ['required', 'string', ...($descriptionMin > 0 ? ['min:'.$descriptionMin] : []), 'max:'.self::DESCRIPTION_MAX]];

        foreach (self::LISTS as $list) {
            $rules[$list] = ['nullable', 'array', 'max:'.self::ITEMS_MAX];
            $rules[$list.'.*'] = ['string', ...($itemMin > 0 ? ['min:'.$itemMin] : []), 'max:'.self::ITEM_MAX];
        }

        return $rules;
    }

    /**
     * The three lists from validated data, cleaned (null when empty).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, list<string>|null>
     */
    public static function listsFrom(array $data): array
    {
        $lists = [];

        foreach (self::LISTS as $list) {
            $items = array_slice(self::cleanList($data[$list] ?? []), 0, self::ITEMS_MAX);
            $lists[$list] = $items === [] ? null : $items;
        }

        return $lists;
    }

    /**
     * Old rich-text descriptions become plain text: paragraphs and list items become lines.
     */
    public static function htmlToText(string $html): string
    {
        $text = preg_replace('/<\/(p|div|h[1-6])>/i', "\n\n", $html) ?? $html;
        $text = preg_replace('/<br\s*\/?>|<\/li>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);

        return self::cleanDescription($text);
    }
}
