<?php

namespace App\Support\Teach;

use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Payment;
use App\Models\TeachingClass;
use App\Support\Currency;
use App\Support\LibyanPhone;
use App\Support\PaymentPresenter;
use App\Support\TulabiClock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * «المدفوعات»: every payment the instructor recorded, across all their courses, with filters
 * (period, course, method, status, search) and the totals of what is shown. Shared by the
 * website's page and the app (/api/v1/teach/payments). Recording, editing and voiding are
 * App\Support\Payments; this only gathers and filters.
 */
class PaymentOverview
{
    private const STATUSES = ['valid', 'voided', 'all'];

    private const PER_PAGE = 25;

    /**
     * @param  array<string, mixed>  $query  the filters as sent (from, to, course, method, status, q)
     * @return array<string, mixed>
     */
    public function for(Instructor $instructor, array $query): array
    {
        $courses = $instructor->classes()
            ->where('status', '!=', TeachingClass::STATUS_DRAFT)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get(['id', 'title']);

        $input = Validator::make($query, [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'course' => ['nullable', 'integer', Rule::in($courses->pluck('id')->all())],
            'method' => ['nullable', Rule::in(Payment::MANUAL_METHODS)],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'q' => ['nullable', 'string', 'max:100'],
        ])->validate();

        $filters = [
            'from' => $input['from'] ?? null,
            'to' => $input['to'] ?? null,
            'course' => isset($input['course']) ? (int) $input['course'] : null,
            'method' => $input['method'] ?? null,
            'status' => $input['status'] ?? 'valid',
            'q' => trim((string) ($input['q'] ?? '')),
        ];

        $base = $this->filtered($instructor, $filters);

        $counted = (clone $base)->notVoided()->get(['amount', 'method']);
        $byMethod = $counted->groupBy('method')->map(fn ($payments, $method) => [
            'method' => $method,
            'count' => $payments->count(),
            'total' => self::sum($payments),
        ])->values();

        $payments = (clone $base)
            ->when($filters['status'] === 'valid', fn (Builder $query) => $query->whereNull('voided_at'))
            ->when($filters['status'] === 'voided', fn (Builder $query) => $query->whereNotNull('voided_at'))
            ->with(['enrollment.user', 'enrollment.teachingClass'])
            ->latest('paid_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Payment $payment) => $this->row($payment));

        return [
            'payments' => $payments,
            'filters' => $filters,
            'courses' => $courses,
            'methods' => Payment::MANUAL_METHODS,
            'totals' => [
                'collected' => self::sum($counted),
                'count' => $counted->count(),
                'voided' => (clone $base)->whereNotNull('voided_at')->count(),
                'by_method' => $byMethod,
            ],
            'payable' => $this->payable($instructor),
            'today' => TulabiClock::today(),
            'monthStart' => TulabiClock::now()->startOfMonth()->toDateString(),
        ];
    }

    /**
     * The instructor's payments narrowed by everything but the status (the totals need both sides).
     *
     * @param  array{from: ?string, to: ?string, course: ?int, method: ?string, q: string}  $filters
     * @return Builder<Payment>
     */
    private function filtered(Instructor $instructor, array $filters): Builder
    {
        $query = Payment::query()->where('instructor_id', $instructor->getKey());

        if ($filters['from'] !== null) {
            $query->where('paid_at', '>=', TulabiClock::startOfDayUtc($filters['from']));
        }

        if ($filters['to'] !== null) {
            $query->where('paid_at', '<=', TulabiClock::endOfDayUtc($filters['to']));
        }

        if ($filters['course'] !== null) {
            $query->whereHas('enrollment', fn (Builder $enrollment) => $enrollment->where('class_id', $filters['course']));
        }

        if ($filters['method'] !== null) {
            $query->where('method', $filters['method']);
        }

        if ($filters['q'] !== '') {
            $text = $filters['q'];
            $digits = ltrim((string) preg_replace('/\D/', '', LibyanPhone::westernDigits($text)), '0');

            $query->where(fn (Builder $match) => $match
                ->where('receipt_number', 'like', '%'.$text.'%')
                ->orWhereHas('enrollment.user', fn (Builder $user) => $user
                    ->where('name', 'like', '%'.$text.'%')
                    ->when($digits !== '', fn (Builder $q) => $q->orWhere('phone', 'like', '%'.$digits.'%'))));
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Payment $payment): array
    {
        $enrollment = $payment->enrollment;
        $user = $enrollment?->user;

        return [
            ...PaymentPresenter::payment($payment),
            'enrollment_id' => $enrollment?->id,
            'student' => $user === null ? null : [
                'name' => $user->name,
                'phone' => LibyanPhone::display($user->phone),
            ],
            'class' => $enrollment?->teachingClass === null ? null : [
                'id' => $enrollment->teachingClass->id,
                'title' => $enrollment->teachingClass->title,
            ],
        ];
    }

    /**
     * Students who still owe something, for «تسجيل دفعة» on this page.
     *
     * @return list<array{id: int, name: string, class: string, remaining: string}>
     */
    private function payable(Instructor $instructor): array
    {
        return Enrollment::query()
            ->where('status', Enrollment::STATUS_ACCEPTED)
            ->whereHas('teachingClass', fn (Builder $class) => $class
                ->where('instructor_id', $instructor->getKey())
                ->where('status', '!=', TeachingClass::STATUS_CANCELED))
            ->with(['user', 'teachingClass', 'payments'])
            ->get()
            ->filter(fn (Enrollment $enrollment) => $enrollment->user !== null && $enrollment->remainingMillis() > 0)
            ->sortBy(fn (Enrollment $enrollment) => mb_strtolower($enrollment->user->name.' '.$enrollment->teachingClass->title))
            ->map(fn (Enrollment $enrollment) => [
                'id' => $enrollment->id,
                'name' => $enrollment->user->name,
                'class' => $enrollment->teachingClass->title,
                'remaining' => Currency::format(Currency::fromMillis($enrollment->remainingMillis())),
            ])
            ->values()
            ->all();
    }

    /** @param  iterable<Payment>  $payments */
    private static function sum(iterable $payments): string
    {
        $millis = 0;

        foreach ($payments as $payment) {
            $millis += Currency::toMillis($payment->amount);
        }

        return Currency::format(Currency::fromMillis($millis));
    }
}
