<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * «طلابي» for an instructor: everyone who was ever accepted in one of their courses, one row per
 * person with their places across the courses and what they owe. Requests that were never accepted
 * are not students yet (they are in «طلبات الانضمام»). The instructor reads only their own
 * courses' enrollments, so a student shared with another instructor shows only the part that is theirs.
 */
class InstructorStudents
{
    /**
     * @return Collection<int, array<string, mixed>> sorted by name; each has the people-level
     *                                               fields and its enrollments (see row())
     */
    public function all(Instructor $instructor, ?int $classId = null): Collection
    {
        $enrollments = Enrollment::query()
            ->whereHas('teachingClass', fn ($query) => $query->where('instructor_id', $instructor->getKey()))
            ->where(fn ($query) => $query
                ->where('status', Enrollment::STATUS_ACCEPTED)
                ->orWhereNotNull('accepted_at'))
            ->when($classId !== null, fn ($query) => $query->where('class_id', $classId))
            ->with(['user', 'teachingClass', 'payments'])
            ->oldest('accepted_at')
            ->oldest('id')
            ->get()
            ->filter(fn (Enrollment $enrollment) => $enrollment->user !== null);

        return $enrollments
            ->groupBy('user_id')
            ->map(fn (Collection $group) => $this->row($group))
            ->sortBy(fn (array $row) => mb_strtolower($row['name']))
            ->values();
    }

    /**
     * Keep the rows whose name or phone contains the search text (digits match the phone in any format).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function search(Collection $rows, ?string $text): Collection
    {
        $text = trim((string) $text);

        if ($text === '') {
            return $rows;
        }

        $digits = preg_replace('/\D/', '', LibyanPhone::westernDigits($text)) ?? '';

        return $rows->filter(fn (array $row) => mb_stripos($row['name'], $text) !== false
            || ($digits !== '' && str_contains((string) preg_replace('/\D/', '', $row['phone_e164']), ltrim($digits, '0'))));
    }

    /**
     * @param  Collection<int, Enrollment>  $group  one user's enrollments in this instructor's courses
     * @return array<string, mixed>
     */
    private function row(Collection $group): array
    {
        /** @var User $user */
        $user = $group->first()->user;

        $active = $group->filter(fn (Enrollment $enrollment) => $enrollment->isActiveMember());
        $paid = $group->sum(fn (Enrollment $enrollment) => $enrollment->paidMillis());
        $remaining = $group->sum(fn (Enrollment $enrollment) => $enrollment->remainingMillis());

        return [
            'user_id' => $user->getKey(),
            'name' => $user->name,
            'has_name' => $user->hasName(),
            'phone' => LibyanPhone::display($user->phone),
            'phone_e164' => $user->phone,
            'whatsapp' => WhatsApp::link($user->phone, __('students.whatsapp_hello', ['name' => $user->name])),
            'is_active' => $active->isNotEmpty(),
            'is_instructor' => $user->instructor()->exists(),
            'account_active' => $user->isActive(),
            'active_count' => $active->count(),
            'courses_count' => $group->count(),
            // The enrollment a password link is issued from (any active one).
            'reset_enrollment_id' => $active->first()?->getKey(),
            'paid' => Currency::format(Currency::fromMillis($paid)),
            'remaining' => Currency::format(Currency::fromMillis($remaining)),
            'remaining_millis' => $remaining,
            'enrollments' => $group
                ->sortByDesc(fn (Enrollment $enrollment) => $enrollment->accepted_at?->timestamp ?? 0)
                ->values()
                ->map(fn (Enrollment $enrollment) => [
                    'id' => $enrollment->getKey(),
                    'status' => $enrollment->displayStatus(),
                    'accepted_at' => $enrollment->accepted_at?->toIso8601String(),
                    'fee' => PaymentPresenter::totals($enrollment)['fee'],
                    'paid' => PaymentPresenter::totals($enrollment)['paid'],
                    'remaining' => PaymentPresenter::totals($enrollment)['remaining'],
                    'class' => [
                        'id' => $enrollment->teachingClass->getKey(),
                        'title' => $enrollment->teachingClass->title,
                    ],
                ])
                ->all(),
        ];
    }
}
