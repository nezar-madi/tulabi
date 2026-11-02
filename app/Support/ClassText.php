<?php

namespace App\Support;

use App\Models\TeachingClass;
use Illuminate\Support\Carbon;

/**
 * A class's dates, days, times and price as people read them in Libya (the public pages are
 * rendered on the server, like rqmana's center website). Same wording as resources/js/utils/classFormat.js.
 */
class ClassText
{
    /** Saturday first (Carbon numbers: 0 = Sunday ... 6 = Saturday). */
    public const WEEK_ORDER = [6, 0, 1, 2, 3, 4, 5];

    private const DAYS = [0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];

    private const MONTHS = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

    /** "9 نوفمبر 2026" */
    public static function date(?Carbon $date): string
    {
        return $date === null ? '' : $date->day.' '.self::MONTHS[$date->month].' '.$date->year;
    }

    public static function monthName(Carbon $date): string
    {
        return self::MONTHS[$date->month];
    }

    /** "السبت، الإثنين، الأربعاء" */
    public static function days(?array $days): string
    {
        $days = $days ?? [];

        return implode('، ', array_map(
            fn (int $day) => self::DAYS[$day],
            array_values(array_filter(self::WEEK_ORDER, fn (int $day) => in_array($day, $days, true))),
        ));
    }

    /**
     * When the course meets: "يوميًا", "السبت، الإثنين" or the picked dates ("5 نوفمبر، 12 نوفمبر ...").
     */
    public static function schedule(TeachingClass $class): string
    {
        return match ($class->schedule_type) {
            TeachingClass::SCHEDULE_DAILY => 'يوميًا',
            TeachingClass::SCHEDULE_MANUAL => self::manualDates($class->manual_dates ?? []),
            default => self::days($class->days),
        };
    }

    /**
     * Hand-picked dates, in order: the first few in full, then how many more.
     *
     * @param  list<string>  $dates  Y-m-d
     */
    public static function manualDates(array $dates, int $show = 4): string
    {
        sort($dates);
        $shown = array_map(fn (string $date) => self::date(Carbon::parse($date)), array_slice($dates, 0, $show));
        $more = count($dates) - $show;

        return implode('، ', $shown).($more > 0 ? ' و'.$more.' '.($more === 1 ? 'تاريخ آخر' : 'تواريخ أخرى') : '');
    }

    /** "17:00" -> "5:00 م" */
    public static function time(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        [$hours, $minutes] = array_map('intval', explode(':', $value));
        $hour12 = $hours % 12 === 0 ? 12 : $hours % 12;

        return $hour12.':'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT).' '.($hours < 12 ? 'ص' : 'م');
    }

    /** "5:00 م - 7:00 م" */
    public static function timeRange(TeachingClass $class): string
    {
        if ($class->start_time === null) {
            return '';
        }

        return self::time($class->start_time).($class->end_time ? ' - '.self::time($class->end_time) : '');
    }

    /** "300 د.ل" or "مجاني" */
    public static function price(TeachingClass $class): string
    {
        return (float) $class->price === 0.0
            ? __('site.free')
            : Currency::format($class->price).' '.($class->currency_code === Currency::CODE ? Currency::SYMBOL : $class->currency_code);
    }
}
