<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\CancelClassRequest;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\ReactivateClassRequest;
use App\Http\Requests\Teach\SaveClassRequest;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Program;
use App\Models\TeachingClass;
use App\Support\ClassImage;
use App\Support\ClassLifecycle;
use App\Support\ClassPresenter;
use App\Support\Currency;
use App\Support\TulabiClock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * The instructor's classes. Every query starts from the signed-in instructor, and every
 * {class} is checked to be theirs (FormRequest first, ownClass() second).
 */
class ClassController extends Controller
{
    use FindsOwnClass;

    private const FILTERS = ['active', 'draft', 'canceled'];

    public function __construct(private readonly ClassLifecycle $lifecycle, private readonly ClassImage $images) {}

    public function index(Request $request): Response
    {
        $instructor = $this->instructor($request);
        $query = $request->validate([
            'filter' => ['nullable', Rule::in(self::FILTERS)],
            // Narrowing the list: title, when it runs, the start date, price, type and program.
            'q' => ['nullable', 'string', 'max:100'],
            'when' => ['nullable', Rule::in(['upcoming', 'running', 'ended'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'price' => ['nullable', Rule::in(['paid', 'free'])],
            'type' => ['nullable', Rule::in([TeachingClass::TYPE_IN_PERSON, TeachingClass::TYPE_ONLINE])],
            'program' => ['nullable', 'string', 'regex:/^(none|\d{1,10})$/'],
        ]);
        $filter = $query['filter'] ?? 'active';
        $narrow = collect($query)->except('filter')->filter(fn ($value) => $value !== null && $value !== '')->all();
        $today = TulabiClock::today();

        // Classes that reached their start date below their minimum are suspended before they are listed.
        $this->lifecycle->suspendUnderfilled($instructor->getKey());

        $statuses = match ($filter) {
            'draft' => [TeachingClass::STATUS_DRAFT],
            'canceled' => [TeachingClass::STATUS_CANCELED],
            default => [TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED, TeachingClass::STATUS_SUSPENDED],
        };

        $classes = $instructor->classes()
            ->whereIn('status', $statuses)
            ->when($narrow['q'] ?? null, fn ($query, string $q) => $query->where(
                'title', 'like', '%'.addcslashes($q, '\\%_').'%',
            ))
            ->when($narrow['when'] ?? null, fn ($query, string $when) => match ($when) {
                'upcoming' => $query->whereDate('starts_on', '>', $today),
                'running' => $query->whereDate('starts_on', '<=', $today)
                    ->where(fn ($inner) => $inner->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today)),
                default => $query->whereDate('ends_on', '<', $today),
            })
            ->when($narrow['from'] ?? null, fn ($query, string $from) => $query->whereDate('starts_on', '>=', $from))
            ->when($narrow['to'] ?? null, fn ($query, string $to) => $query->whereDate('starts_on', '<=', $to))
            ->when($narrow['price'] ?? null, fn ($query, string $price) => $query->where('price', $price === 'paid' ? '>' : '=', 0))
            ->when($narrow['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($narrow['program'] ?? null, fn ($query, string $program) => $program === 'none'
                ? $query->whereNull('program_id')
                : $query->where('program_id', (int) $program))
            // The card shows the seats taken and the requests waiting.
            ->withCount([
                'enrollments as accepted_count' => fn ($query) => $query->where('status', Enrollment::STATUS_ACCEPTED),
                'enrollments as requested_count' => fn ($query) => $query->where('status', Enrollment::STATUS_REQUESTED),
                'enrollments as price_lock_count' => fn ($query) => TeachingClass::lockingPrice($query),
            ])
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (TeachingClass $class) => [
                ...ClassPresenter::forOwner($class),
                'accepted_count' => $class->accepted_count,
                'requested_count' => $class->requested_count,
            ]);

        return Inertia::render('Teach/Classes/Index', [
            'filter' => $filter,
            'narrow' => (object) $narrow,
            'programs' => $instructor->programs()->orderBy('name')->get(['id', 'name']),
            'draftCount' => $instructor->classes()->where('status', TeachingClass::STATUS_DRAFT)->count(),
            'classes' => $classes,
            'hasSlug' => $instructor->slug !== null,
            'slug' => $instructor->slug,
            'today' => $today,
        ]);
    }

    public function create(Request $request): Response
    {
        $instructor = $this->instructor($request);

        return Inertia::render('Teach/Classes/Form', [
            'item' => null,
            'currency' => Currency::CODE,
            'programs' => $programs = $this->programOptions($instructor, null),
            // «فصل جديد من هذا البرنامج»: the programs page links here with the program chosen.
            'selectedProgram' => collect($programs)->firstWhere('id', $request->integer('program'))['id'] ?? null,
            'today' => TulabiClock::today(),
        ]);
    }

    public function store(SaveClassRequest $request): RedirectResponse
    {
        // The image is decoded first, so an unreadable file stops everything before the class is saved.
        $jpeg = $this->renderImage($request);

        $class = new TeachingClass($request->classData());
        $class->forceFill([
            'instructor_id' => $this->instructor($request)->getKey(),
            'currency_code' => Currency::CODE,
            'status' => TeachingClass::STATUS_DRAFT,
        ])->save();

        if ($jpeg !== null) {
            $this->images->attach($class, $jpeg);
        } elseif ($class->program?->image_path !== null && $class->promo_video_url === null && ! $request->boolean('no_template_image')) {
            // The program is the course's template: with no image or video of its own, it starts with the program's image.
            $this->images->copyFromProgram($class, $class->program);
        }

        // The draft page opens with the «تم إنشاء الفصل» dialog (once).
        return redirect()->route('teach.classes.edit', $class)->with('classCreated', true);
    }

    public function edit(Request $request, TeachingClass $class): Response
    {
        $class = $this->ownClass($request, $class);
        $this->lifecycle->checkStart($class);

        return Inertia::render('Teach/Classes/Form', [
            'item' => ClassPresenter::forOwner($class),
            'currency' => $class->currency_code,
            'programs' => $this->programOptions($this->instructor($request), $class->program_id),
            'today' => TulabiClock::today(),
            'meetingShareUrl' => ClassPresenter::meetingShareUrl($class),
            'studentCounts' => [
                'accepted' => $class->enrollments()->where('status', Enrollment::STATUS_ACCEPTED)->count(),
                'requested' => $class->isCanceled() ? 0 : $class->enrollments()->where('status', Enrollment::STATUS_REQUESTED)->count(),
            ],
            'meetingChanged' => (bool) $request->session()->get('meetingChanged'),
            // After a save that moved the dates, hours or place of a class with students.
            'scheduleShareUrl' => $request->session()->get('scheduleChanged') ? ClassPresenter::scheduleShareUrl($class) : null,
            'justCreated' => (bool) $request->session()->get('classCreated'),
            'publicUrl' => $class->isPubliclyVisible() || $class->status === TeachingClass::STATUS_DRAFT
                ? $this->publicUrl($class)
                : null,
        ]);
    }

    public function update(SaveClassRequest $request, TeachingClass $class): RedirectResponse
    {
        $class = $this->ownClass($request, $class);
        $jpeg = $this->renderImage($request);

        $class->fill($request->classData());
        // A new meeting link is marked for the students and offered to the instructor to send on WhatsApp.
        $meetingChanged = $class->isDirty('meeting_url') && $class->meeting_url !== null;

        if ($meetingChanged) {
            $class->forceFill(['meeting_url_changed_at' => now()]);
        }

        // New dates, hours or place: accepted students see «تغيّر الموعد» and the instructor is offered WhatsApp.
        $scheduleChanged = $class->isDirty(TeachingClass::SCHEDULE_FIELDS) && $class->hasAcceptedStudents();

        if ($scheduleChanged) {
            $class->forceFill(['schedule_changed_at' => now()]);
        }

        // «تعليق البدء تلقائيًا» looks at the start date once: a new future start is checked when it
        // comes; a class already past its start (or moved to today) is not checked any more.
        $started = $class->starts_on->toDateString() <= TulabiClock::today();

        if ($class->isDirty('starts_on') || $class->start_checked_at === null) {
            $class->forceFill(['start_checked_at' => $started ? ($class->start_checked_at ?? now()) : null]);
        }

        $class->save();

        if ($jpeg !== null) {
            $this->images->attach($class, $jpeg);
        } elseif ($request->boolean('remove_image')) {
            $this->images->delete($class);
        }

        return back()->with('status', __('classes.saved'))
            ->with('meetingChanged', $meetingChanged)
            ->with('scheduleChanged', $scheduleChanged);
    }

    public function publish(InstructorActionRequest $request, TeachingClass $class): RedirectResponse
    {
        $this->lifecycle->publish($this->ownClass($request, $class));

        return back()->with('status', __('classes.published'));
    }

    /** A suspended class starts again on a new date. */
    public function reactivate(ReactivateClassRequest $request, TeachingClass $class): RedirectResponse
    {
        $class = $this->ownClass($request, $class);
        $this->lifecycle->reactivate($class, (string) $request->validated('starts_on'));

        // The dates moved: with students, the edit page offers the new schedule on WhatsApp.
        return back()->with('status', __('classes.reactivated'))->with('scheduleChanged', $class->hasAcceptedStudents());
    }

    public function close(InstructorActionRequest $request, TeachingClass $class): RedirectResponse
    {
        $this->lifecycle->close($this->ownClass($request, $class));

        return back()->with('status', __('classes.closed'));
    }

    public function cancel(CancelClassRequest $request, TeachingClass $class): RedirectResponse
    {
        $this->lifecycle->cancel($this->ownClass($request, $class), $request->validated('reason'));

        return back()->with('status', __('classes.canceled'));
    }

    public function destroy(InstructorActionRequest $request, TeachingClass $class): RedirectResponse
    {
        $this->lifecycle->delete($this->ownClass($request, $class));

        return redirect()->route('teach.classes.index', ['filter' => 'draft'])->with('status', __('classes.deleted'));
    }

    /**
     * The programs a course may be put in: the active ones, and the one it is already in.
     *
     * @return list<array{id: int, name: string, description: string, image_url: string|null}>
     */
    private function programOptions(Instructor $instructor, ?int $current): array
    {
        return $instructor->programs()
            ->where(fn ($query) => $query->where('is_active', true)->when($current, fn ($q) => $q->orWhere('id', $current)))
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'learn', 'requirements', 'audience', 'promo_video_url', 'image_path'])
            ->map(fn (Program $program) => [
                'id' => $program->id,
                'name' => $program->name,
                'description' => $program->description,
                'learn' => $program->learn ?? [],
                'requirements' => $program->requirements ?? [],
                'audience' => $program->audience ?? [],
                'promo_video_url' => $program->promo_video_url,
                'image_url' => $program->imageUrl(),
            ])
            ->all();
    }

    private function renderImage(SaveClassRequest $request): ?string
    {
        $file = $request->newImage();

        if ($file === null) {
            return null;
        }

        try {
            return $this->images->render($file);
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['image' => __('classes.image_unreadable')]);
        }
    }

    private function publicUrl(TeachingClass $class): ?string
    {
        $slug = $class->instructor->slug;

        return $slug === null ? null : route('classes.show', ['slug' => $slug, 'class' => $class->id]);
    }
}
