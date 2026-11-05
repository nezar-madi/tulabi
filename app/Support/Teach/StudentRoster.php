<?php

namespace App\Support\Teach;

use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Support\Currency;
use App\Support\InstructorStudents;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * «الطلاب»: everyone accepted in any of the instructor's courses, in one list with filters
 * and paging. Shared by the website's page and the app (/api/v1/teach/students).
 */
class StudentRoster
{
    private const FILTERS = ['active', 'past', 'all'];

    private const PER_PAGE = 15;

    public function __construct(private readonly InstructorStudents $students) {}

    /**
     * @param  array<string, mixed>  $query  the filters as sent (filter, q, course, page)
     * @return array<string, mixed>
     */
    public function for(Instructor $instructor, array $query, string $url): array
    {
        $courses = $instructor->classes()
            ->where('status', '!=', TeachingClass::STATUS_DRAFT)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get(['id', 'title']);

        $input = Validator::make($query, [
            'filter' => ['nullable', Rule::in(self::FILTERS)],
            'q' => ['nullable', 'string', 'max:100'],
            'course' => ['nullable', 'integer', Rule::in($courses->pluck('id')->all())],
        ])->validate();
        $filter = $input['filter'] ?? 'active';
        $course = isset($input['course']) ? (int) $input['course'] : null;

        $everyone = $this->students->all($instructor, $course);
        $counts = [
            'active' => $everyone->where('is_active', true)->count(),
            'past' => $everyone->where('is_active', false)->count(),
            'all' => $everyone->count(),
        ];

        $rows = $this->students->search(
            match ($filter) {
                'active' => $everyone->where('is_active', true),
                'past' => $everyone->where('is_active', false),
                default => $everyone,
            },
            $input['q'] ?? null,
        )->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = (new LengthAwarePaginator(
            $rows->forPage($page, self::PER_PAGE)->values()->map(fn (array $row) => collect($row)->except(['phone_e164', 'remaining_millis'])->all()),
            $rows->count(),
            self::PER_PAGE,
            $page,
            ['path' => $url, 'query' => $query],
        ));

        return [
            'students' => $paginator,
            'filter' => $filter,
            'q' => $input['q'] ?? '',
            'course' => $course,
            'courses' => $courses,
            'counts' => $counts,
            'totals' => [
                'remaining' => Currency::format(Currency::fromMillis((int) $everyone->sum('remaining_millis'))),
            ],
        ];
    }
}
