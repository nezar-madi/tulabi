<?php

namespace App\Support;

use App\Models\Instructor;
use App\Models\TeachingClass;
use Illuminate\Support\Carbon;

/**
 * An instructor cannot teach two classes at once: a class's sessions may not overlap those of
 * another active class (published, closed or suspended) of the same instructor. Two classes clash
 * when their periods overlap, they meet on a common day in that overlap, and their hours overlap
 * (one ending at 17:00 and the other starting at 17:00 is fine). In person or online makes no
 * difference. Drafts and canceled classes are not checked against.
 */
class ScheduleClash
{
    /** Statuses whose sessions are taken. */
    public const ACTIVE = [
        TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED, TeachingClass::STATUS_SUSPENDED,
    ];

    /**
     * The first clash with another active class: that class and the first day both meet (Y-m-d).
     *
     * @param  array{starts_on: string, ends_on: ?string, schedule_type: string, days?: ?array<int>, manual_dates?: ?array<string>, start_time: ?string, end_time: ?string}  $schedule
     * @return array{class: TeachingClass, date: string}|null
     */
    public static function find(Instructor $instructor, array $schedule, ?int $exceptId = null): ?array
    {
        $start = self::time($schedule['start_time'] ?? null);
        $end = self::time($schedule['end_time'] ?? null);

        if ($start === null || $end === null || $end <= $start) {
            return null;
        }

        $mine = self::meetings($schedule);
        $first = $mine['from'];
        $last = $mine['to'];

        $others = $instructor->classes()
            ->whereIn('status', self::ACTIVE)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            // Periods overlap (an old class with no end date runs on).
            ->whereDate('starts_on', '<=', $last)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $first))
            ->orderBy('starts_on')
            ->get();

        foreach ($others as $other) {
            // Hours overlap (compared here: the stored text may be "17:00" or "17:00:00").
            if (! (self::time($other->start_time) < $end && self::time($other->end_time) > $start)) {
                continue;
            }

            $date = self::commonDay($mine, self::meetings([
                'starts_on' => $other->starts_on->toDateString(),
                'ends_on' => $other->ends_on?->toDateString(),
                'schedule_type' => $other->schedule_type,
                'days' => $other->days,
                'manual_dates' => $other->manual_dates,
            ]));

            if ($date !== null) {
                return ['class' => $other, 'date' => $date];
            }
        }

        return null;
    }

    /**
     * When a schedule meets: its period and its rule.
     *
     * @param  array<string, mixed>  $schedule
     * @return array{from: string, to: string, type: string, days: list<int>, dates: list<string>}
     */
    private static function meetings(array $schedule): array
    {
        $type = (string) $schedule['schedule_type'];
        $dates = $type === TeachingClass::SCHEDULE_MANUAL ? array_values(array_map('strval', (array) ($schedule['manual_dates'] ?? []))) : [];
        sort($dates);
        $to = $schedule['ends_on'] ?? null;

        if ($to === null || $to === '') {
            // No end date (older classes): hand-picked dates end with the last one, the rest run on.
            $to = $dates !== [] ? end($dates) : '2099-12-31';
        }

        return [
            'from' => (string) $schedule['starts_on'],
            'to' => (string) $to,
            'type' => $type,
            'days' => array_map('intval', (array) ($schedule['days'] ?? [])),
            'dates' => $dates,
        ];
    }

    /**
     * The first day inside both periods on which both meet, or null.
     *
     * @param  array{from: string, to: string, type: string, days: list<int>, dates: list<string>}  $a
     * @param  array{from: string, to: string, type: string, days: list<int>, dates: list<string>}  $b
     */
    private static function commonDay(array $a, array $b): ?string
    {
        $from = max($a['from'], $b['from']);
        $to = min($a['to'], $b['to']);

        if ($from > $to) {
            return null;
        }

        // Hand-picked dates: check each one.
        foreach ([[$a, $b], [$b, $a]] as [$picked, $other]) {
            if ($picked['type'] === TeachingClass::SCHEDULE_MANUAL) {
                foreach ($picked['dates'] as $date) {
                    if ($date >= $from && $date <= $to && self::meetsOn($picked, $date) && self::meetsOn($other, $date)) {
                        return $date;
                    }
                }

                return null;
            }
        }

        // Daily and weekly repeat every week: the first seven days of the overlap say it all.
        $day = Carbon::parse($from);
        $end = Carbon::parse($to);

        for ($i = 0; $i < 7 && $day->lte($end); $i++, $day->addDay()) {
            $date = $day->toDateString();

            if (self::meetsOn($a, $date) && self::meetsOn($b, $date)) {
                return $date;
            }
        }

        return null;
    }

    /** @param  array{type: string, days: list<int>, dates: list<string>}  $schedule */
    private static function meetsOn(array $schedule, string $date): bool
    {
        return match ($schedule['type']) {
            TeachingClass::SCHEDULE_DAILY => true,
            TeachingClass::SCHEDULE_WEEKLY => in_array(Carbon::parse($date)->dayOfWeek, $schedule['days'], true),
            TeachingClass::SCHEDULE_MANUAL => in_array($date, $schedule['dates'], true),
            default => false,
        };
    }

    /** "17:00" or "17:00:00" → "17:00:00". */
    private static function time(?string $value): ?string
    {
        if ($value === null || ! preg_match('/^(\d{2}):(\d{2})/', $value, $match)) {
            return null;
        }

        return "{$match[1]}:{$match[2]}:00";
    }

    /**
     * The error text: the other class, the day and its hours.
     *
     * @param  array{class: TeachingClass, date: string}  $clash
     */
    public static function message(array $clash): string
    {
        /** @var TeachingClass $class */
        $class = $clash['class'];

        return __('classes.schedule_clash', [
            'title' => $class->title,
            'date' => ClassText::date(Carbon::parse($clash['date'])),
            'time' => ClassText::timeRange($class),
        ]);
    }
}
