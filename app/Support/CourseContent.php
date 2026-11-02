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

    /** Plain description: normalised line breaks, no more than one blank line in a row. */
    public static function cleanDescription(?string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", (string) $text);

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? '');
    }

    /**
     * Validation rules for the shared content fields.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $rules = ['description' => ['required', 'string', 'max:'.self::DESCRIPTION_MAX]];

        foreach (self::LISTS as $list) {
            $rules[$list] = ['nullable', 'array', 'max:'.self::ITEMS_MAX];
            $rules[$list.'.*'] = ['string', 'max:'.self::ITEM_MAX];
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
