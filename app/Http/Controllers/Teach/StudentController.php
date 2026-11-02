<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Models\TeachingClass;
use App\Support\Currency;
use App\Support\InstructorStudents;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * «الطلاب»: everyone accepted in any of the instructor's courses, in one list. Joining, removing,
 * payments and password links keep their own actions (EnrollmentController, PaymentController);
 * this page only gathers them.
 */
class StudentController extends Controller
{
    use FindsOwnClass;

    private const FILTERS = ['active', 'past', 'all'];

    private const PER_PAGE = 15;

    public function __construct(private readonly InstructorStudents $students) {}

    public function index(Request $request): Response
    {
        $instructor = $this->instructor($request);

        $courses = $instructor->classes()
            ->where('status', '!=', TeachingClass::STATUS_DRAFT)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get(['id', 'title']);

        $input = $request->validate([
            'filter' => ['nullable', Rule::in(self::FILTERS)],
            'q' => ['nullable', 'string', 'max:100'],
            'course' => ['nullable', 'integer', Rule::in($courses->pluck('id')->all())],
        ]);
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
            ['path' => $request->url(), 'query' => $request->query()],
        ));

        return Inertia::render('Teach/Students', [
            'students' => $paginator,
            'filter' => $filter,
            'q' => $input['q'] ?? '',
            'course' => $course,
            'courses' => $courses,
            'counts' => $counts,
            'totals' => [
                'remaining' => Currency::format(Currency::fromMillis((int) $everyone->sum('remaining_millis'))),
            ],
            'resetLink' => $request->session()->get('resetLink'),
        ]);
    }
}
