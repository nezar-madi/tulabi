<?php

namespace App\Support;

use App\Models\TeachingClass;

/**
 * The shapes a class is sent to pages in. The public shape never has meeting_url:
 * the meeting link is for accepted students only.
 */
class ClassPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forPublic(TeachingClass $class): array
    {
        return [
            'id' => $class->id,
            'title' => $class->title,
            'description' => $class->description,
            'learn' => $class->learn ?? [],
            'requirements' => $class->requirements ?? [],
            'audience' => $class->audience ?? [],
            'video_embed_url' => VideoEmbed::embedUrl($class->promo_video_url),
            'type' => $class->type,
            'location' => $class->type === TeachingClass::TYPE_IN_PERSON ? $class->location : null,
            'map_url' => $class->type === TeachingClass::TYPE_IN_PERSON ? $class->map_url : null,
            'image_url' => $class->imageUrl(),
            'starts_on' => $class->starts_on?->toDateString(),
            'ends_on' => $class->ends_on?->toDateString(),
            'schedule_type' => $class->schedule_type,
            'days' => $class->days ?? [],
            'manual_dates' => $class->manual_dates ?? [],
            'start_time' => self::time($class->start_time),
            'end_time' => self::time($class->end_time),
            'price' => self::price($class->price),
            'currency' => $class->currency_code,
            'seats' => $class->seats,
            'status' => $class->status,
            'ended' => $class->hasEnded(),
        ];
    }

    /**
     * Everything, for the instructor's own pages.
     *
     * @return array<string, mixed>
     */
    public static function forOwner(TeachingClass $class): array
    {
        return [
            ...self::forPublic($class),
            'location' => $class->location,
            'meeting_url' => $class->meeting_url,
            'program_id' => $class->program_id,
            'promo_video_url' => $class->promo_video_url,
            'auto_suspend' => $class->auto_suspend,
            'min_seats' => $class->min_seats,
            'suspended_at' => $class->suspended_at?->toIso8601String(),
            'cancel_reason' => $class->cancel_reason,
            'published_at' => $class->published_at?->toIso8601String(),
            'canceled_at' => $class->canceled_at?->toIso8601String(),
        ];
    }

    /**
     * A WhatsApp message with the meeting link, for the instructor to send to their students
     * (an online class with a link only).
     */
    public static function meetingShareUrl(TeachingClass $class): ?string
    {
        if ($class->type !== TeachingClass::TYPE_ONLINE || $class->meeting_url === null || $class->isCanceled()) {
            return null;
        }

        return WhatsApp::share(__('classes.meeting_whatsapp', ['class' => $class->title, 'url' => $class->meeting_url]));
    }

    /** "17:00:00" from the database becomes "17:00". */
    private static function time(?string $value): ?string
    {
        return $value === null ? null : substr($value, 0, 5);
    }

    /** "300.000" becomes "300", "12.500" becomes "12.5". */
    private static function price(mixed $value): string
    {
        $text = number_format((float) $value, Currency::DECIMALS, '.', '');

        return rtrim(rtrim($text, '0'), '.');
    }
}
