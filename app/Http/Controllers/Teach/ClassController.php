<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\CancelClassRequest;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\SaveClassRequest;
use App\Models\Instructor;
use App\Models\Program;
use App\Models\TeachingClass;
use App\Support\ClassImage;
use App\Support\ClassLifecycle;
use App\Support\ClassPresenter;
use App\Support\Currency;
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
        $filter = $request->validate(['filter' => ['nullable', Rule::in(self::FILTERS)]])['filter'] ?? 'active';

        $statuses = match ($filter) {
            'draft' => [TeachingClass::STATUS_DRAFT],
            'canceled' => [TeachingClass::STATUS_CANCELED],
            default => [TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED],
        };

        $classes = $instructor->classes()
            ->whereIn('status', $statuses)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (TeachingClass $class) => ClassPresenter::forOwner($class));

        return Inertia::render('Teach/Classes/Index', [
            'filter' => $filter,
            'classes' => $classes,
            'hasSlug' => $instructor->slug !== null,
            'slug' => $instructor->slug,
        ]);
    }

    public function create(Request $request): Response
    {
        $instructor = $this->instructor($request);

        return Inertia::render('Teach/Classes/Form', [
            'item' => null,
            'currency' => Currency::CODE,
            'programs' => $this->programOptions($instructor, null),
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
        }

        return redirect()->route('teach.classes.edit', $class)->with('status', __('classes.created'));
    }

    public function edit(Request $request, TeachingClass $class): Response
    {
        $class = $this->ownClass($request, $class);

        return Inertia::render('Teach/Classes/Form', [
            'item' => ClassPresenter::forOwner($class),
            'currency' => $class->currency_code,
            'programs' => $this->programOptions($this->instructor($request), $class->program_id),
            'publicUrl' => $class->isPubliclyVisible() || $class->status === TeachingClass::STATUS_DRAFT
                ? $this->publicUrl($class)
                : null,
        ]);
    }

    public function update(SaveClassRequest $request, TeachingClass $class): RedirectResponse
    {
        $class = $this->ownClass($request, $class);
        $jpeg = $this->renderImage($request);

        $class->fill($request->classData())->save();

        if ($jpeg !== null) {
            $this->images->attach($class, $jpeg);
        } elseif ($request->boolean('remove_image')) {
            $this->images->delete($class);
        }

        return back()->with('status', __('classes.saved'));
    }

    public function publish(InstructorActionRequest $request, TeachingClass $class): RedirectResponse
    {
        $this->lifecycle->publish($this->ownClass($request, $class));

        return back()->with('status', __('classes.published'));
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
     * @return list<array{id: int, name: string}>
     */
    private function programOptions(Instructor $instructor, ?int $current): array
    {
        return $instructor->programs()
            ->where(fn ($query) => $query->where('is_active', true)->when($current, fn ($q) => $q->orWhere('id', $current)))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Program $program) => ['id' => $program->id, 'name' => $program->name])
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
