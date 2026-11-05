<?php

namespace App\Support\Teach;

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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * The instructor's classes: the list with its filters, the form's data, creating and saving.
 * Shared by the website's Teach\ClassController and the app's /api/v1/teach/classes. The callers
 * check that a class is the instructor's own (SaveClassRequest, then FindsOwnClass::ownClass);
 * publish, close, cancel and delete are App\Support\ClassLifecycle.
 */
class ClassEditor
{
    public const FILTERS = ['active', 'draft', 'canceled'];

    public function __construct(private readonly ClassLifecycle $lifecycle, private readonly ClassImage $images) {}

    /**
     * The list: ?filter=active|draft|canceled and narrowing (q, when, from, to, price, type, program).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function list(Instructor $instructor, array $input): array
    {
        $query = Validator::make($input, [
            'filter' => ['nullable', Rule::in(self::FILTERS)],
            // Narrowing the list: title, when it runs, the start date, price, type and program.
            'q' => ['nullable', 'string', 'max:100'],
            'when' => ['nullable', Rule::in(['upcoming', 'running', 'ended'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'price' => ['nullable', Rule::in(['paid', 'free'])],
            'type' => ['nullable', Rule::in([TeachingClass::TYPE_IN_PERSON, TeachingClass::TYPE_ONLINE])],
            'program' => ['nullable', 'string', 'regex:/^(none|\d{1,10})$/'],
        ])->validate();
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

        /** @var LengthAwarePaginator $classes */
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

        return [
            'filter' => $filter,
            'narrow' => (object) $narrow,
            'programs' => $instructor->programs()->orderBy('name')->get(['id', 'name']),
            'draftCount' => $instructor->classes()->where('status', TeachingClass::STATUS_DRAFT)->count(),
            'classes' => $classes,
            'hasSlug' => $instructor->slug !== null,
            'slug' => $instructor->slug,
            'today' => $today,
        ];
    }

    /**
     * What a new class's form needs. $program is a program chosen in advance («فصل جديد من هذا البرنامج»).
     *
     * @return array<string, mixed>
     */
    public function createData(Instructor $instructor, ?int $program = null): array
    {
        $programs = $this->programOptions($instructor, null);

        return [
            'item' => null,
            'currency' => Currency::CODE,
            'programs' => $programs,
            'selectedProgram' => collect($programs)->firstWhere('id', $program)['id'] ?? null,
            'today' => TulabiClock::today(),
        ];
    }

    /**
     * An existing class as its edit form shows it (after the start-date check).
     *
     * @return array<string, mixed>
     */
    public function editData(Instructor $instructor, TeachingClass $class): array
    {
        $this->lifecycle->checkStart($class);

        return [
            'item' => ClassPresenter::forOwner($class),
            'currency' => $class->currency_code,
            'programs' => $this->programOptions($instructor, $class->program_id),
            'today' => TulabiClock::today(),
            'meetingShareUrl' => ClassPresenter::meetingShareUrl($class),
            // «حذف»: a draft, or a canceled / ended class with no accepted student and no payment.
            'canDelete' => $class->canBeDeleted(),
            'studentCounts' => [
                'accepted' => $class->enrollments()->where('status', Enrollment::STATUS_ACCEPTED)->count(),
                'requested' => $class->isCanceled() ? 0 : $class->enrollments()->where('status', Enrollment::STATUS_REQUESTED)->count(),
            ],
            'publicUrl' => $class->isPubliclyVisible() || $class->status === TeachingClass::STATUS_DRAFT
                ? $this->publicUrl($class)
                : null,
        ];
    }

    /** A new class, always a draft. */
    public function create(Instructor $instructor, SaveClassRequest $request): TeachingClass
    {
        // The image is decoded first, so an unreadable file stops everything before the class is saved.
        $jpeg = $this->renderImage($request);

        $class = new TeachingClass($request->classData());
        $class->forceFill([
            'instructor_id' => $instructor->getKey(),
            'currency_code' => Currency::CODE,
            'status' => TeachingClass::STATUS_DRAFT,
        ])->save();

        if ($jpeg !== null) {
            $this->images->attach($class, $jpeg);
        } elseif ($class->program?->image_path !== null && $class->promo_video_url === null && ! $request->boolean('no_template_image')) {
            // The program is the course's template: with no image or video of its own, it starts with the program's image.
            $this->images->copyFromProgram($class, $class->program);
        }

        return $class;
    }

    /**
     * Save a class. Tells whether the meeting link changed (students see «رابط جديد», the instructor is
     * offered WhatsApp) and whether the dates, hours or place moved for a class with students.
     *
     * @return array{meeting_changed: bool, schedule_changed: bool}
     */
    public function update(TeachingClass $class, SaveClassRequest $request): array
    {
        $jpeg = $this->renderImage($request);

        $class->fill($request->classData());
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

        return ['meeting_changed' => $meetingChanged, 'schedule_changed' => $scheduleChanged];
    }

    /**
     * The programs a course may be put in: the active ones, and the one it is already in.
     *
     * @return list<array{id: int, name: string, description: string, image_url: string|null}>
     */
    public function programOptions(Instructor $instructor, ?int $current): array
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
