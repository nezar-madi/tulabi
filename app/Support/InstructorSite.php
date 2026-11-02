<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\TeachingClass;

/**
 * The data of an instructor's public page, in the shape of rqmana's center website
 * ($site and course arrays). meeting_url is never part of it.
 */
class InstructorSite
{
    /**
     * @return array<string, mixed>
     */
    public static function site(Instructor $instructor, bool $isOwner): array
    {
        return [
            'name' => $instructor->display_name,
            'slug' => $instructor->slug,
            'url' => $instructor->publicUrl(),
            'specialty' => $instructor->specialty,
            'city' => $instructor->city,
            'about' => $instructor->bio,
            'photo_url' => $instructor->photoUrl(),
            'links' => ProfileLinks::present($instructor->links),
            'whatsapp_number' => $instructor->whatsapp === null ? null : LibyanPhone::display($instructor->whatsapp),
            'whatsapp_url' => WhatsApp::link(
                $instructor->whatsapp,
                __('instructors.whatsapp_hello', ['name' => $instructor->display_name]),
            ),
            'is_owner' => $isOwner,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function course(TeachingClass $class, Instructor $instructor, ?Enrollment $enrollment = null): array
    {
        $ended = $class->hasEnded();

        return [
            'id' => $class->id,
            'title' => $class->title,
            'description' => $class->description,
            'url' => route('classes.show', ['slug' => $instructor->slug, 'class' => $class->id]),
            'type' => $class->type,
            'type_label' => __('site.type.'.$class->type),
            'location' => $class->type === TeachingClass::TYPE_IN_PERSON ? $class->location : null,
            'map_url' => $class->type === TeachingClass::TYPE_IN_PERSON ? $class->map_url : null,
            'image_url' => $class->imageUrl(),
            'status' => $class->status,
            'status_label' => match (true) {
                $ended => __('site.status.ended'),
                $class->status === TeachingClass::STATUS_CLOSED => __('site.status.closed'),
                $class->status === TeachingClass::STATUS_DRAFT => __('site.status.draft'),
                default => __('site.status.open'),
            },
            'is_open' => $class->acceptsRequests(),
            'ended' => $ended,
            'starts_on' => $class->starts_on,
            'starting_date' => ClassText::date($class->starts_on),
            'ending_date' => ClassText::date($class->ends_on),
            'schedule' => ClassText::days($class->days),
            'time' => ClassText::timeRange($class),
            'price' => ClassText::price($class),
            'is_free' => (float) $class->price === 0.0,
            'seats' => $class->seats,
            'request_url' => route('classes.request', $class->id),
            'request_login_url' => route('classes.request.create', $class->id),
            'whatsapp_url' => WhatsApp::link($instructor->whatsapp, __('site.whatsapp_class', ['title' => $class->title])),
            'enrollment' => $enrollment === null ? null : [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'status_label' => __('site.enrollment.'.$enrollment->status),
                'url' => route('student.classes.show', $enrollment->id),
            ],
        ];
    }
}
