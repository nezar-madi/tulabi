<?php

namespace App\Support\Teach;

use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Payment;
use App\Models\TeachingClass;
use App\Support\ClassLifecycle;
use App\Support\ClassPresenter;
use App\Support\Currency;
use App\Support\TulabiClock;

/**
 * The instructor's home (the website's /teach and the app's /api/v1/teach/home): what the page
 * still needs, money this month, the coming classes.
 */
class TeachHome
{
    public function __construct(private readonly ClassLifecycle $lifecycle) {}

    /**
     * @return array<string, mixed>
     */
    public function for(Instructor $instructor): array
    {
        $today = TulabiClock::today();

        // Classes that reached their start date below their minimum are suspended before they are counted.
        $this->lifecycle->suspendUnderfilled($instructor->getKey());

        $upcoming = $instructor->classes()
            ->whereIn('status', [TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED])
            ->where(fn ($query) => $query
                ->where('ends_on', '>=', $today)
                ->orWhere(fn ($query) => $query->whereNull('ends_on')->where('starts_on', '>=', $today)))
            ->orderBy('starts_on')
            ->orderBy('id')
            ->limit(5)
            ->withCount([
                'enrollments as students_count' => fn ($query) => $query->where('status', Enrollment::STATUS_ACCEPTED),
                'enrollments as price_lock_count' => fn ($query) => TeachingClass::lockingPrice($query),
            ])
            ->get()
            ->map(fn (TeachingClass $class) => [
                ...ClassPresenter::forOwner($class),
                'students_count' => $class->students_count,
            ]);

        return [
            'profile' => [
                'display_name' => $instructor->display_name,
                'slug' => $instructor->slug,
                'public_url' => $instructor->publicUrl(),
                'has_photo' => $instructor->photo_path !== null,
                'has_bio' => trim((string) $instructor->bio) !== '',
            ],
            'counts' => $instructor->classes()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'upcoming' => $upcoming,
            // Accepted students in classes that are not canceled (one person in two classes counts twice).
            'students' => Enrollment::query()
                ->where('status', Enrollment::STATUS_ACCEPTED)
                ->whereHas('teachingClass', fn ($query) => $query
                    ->where('instructor_id', $instructor->getKey())
                    ->where('status', '!=', TeachingClass::STATUS_CANCELED))
                ->count(),
            'money' => $this->money($instructor),
        ];
    }

    /**
     * Collected this month (payments dated this month in Libya, voided ones left out) and
     * still owed by accepted students of running classes.
     *
     * @return array{collected: string, remaining: string, currency: string}
     */
    private function money(Instructor $instructor): array
    {
        [$from, $to] = TulabiClock::monthRangeUtc();

        $collected = Payment::query()
            ->where('instructor_id', $instructor->getKey())
            ->notVoided()
            ->whereBetween('paid_at', [$from, $to])
            ->get(['amount'])
            ->sum(fn (Payment $payment) => Currency::toMillis($payment->amount));

        $remaining = Enrollment::query()
            ->where('status', Enrollment::STATUS_ACCEPTED)
            ->whereHas('teachingClass', fn ($query) => $query
                ->where('instructor_id', $instructor->getKey())
                ->where('status', '!=', TeachingClass::STATUS_CANCELED))
            ->with(['teachingClass', 'payments'])
            ->get()
            ->sum(fn (Enrollment $enrollment) => $enrollment->remainingMillis());

        return [
            'collected' => Currency::format(Currency::fromMillis($collected)),
            'remaining' => Currency::format(Currency::fromMillis($remaining)),
            'currency' => Currency::SYMBOL,
        ];
    }
}
