<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\SaveProgramRequest;
use App\Models\Program;
use App\Support\ClassImage;
use App\Support\ProgramPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * «البرامج التدريبية» of the instructor. Every query starts from the signed-in instructor and
 * every {program} is checked to be theirs (FormRequest first, ownProgram() second).
 */
class ProgramController extends Controller
{
    use FindsOwnClass;

    public function __construct(private readonly ClassImage $images) {}

    public function index(Request $request): Response
    {
        $instructor = $this->instructor($request);

        $programs = $instructor->programs()
            ->with('instructor')
            ->withCount('classes')
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();

        return Inertia::render('Teach/Programs/Index', [
            'programs' => $programs->map(fn (Program $program) => ProgramPresenter::forOwner($program))->values(),
            'counts' => [
                'total' => $programs->count(),
                'active' => $programs->where('is_active', true)->count(),
                'published' => $programs->where('is_active', true)->where('is_published', true)->count(),
            ],
            'hasSlug' => $instructor->slug !== null,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->instructor($request);

        return Inertia::render('Teach/Programs/Form', ['item' => null]);
    }

    public function store(SaveProgramRequest $request): RedirectResponse
    {
        $jpeg = $this->renderImage($request);

        $program = new Program($request->programData());
        $program->forceFill(['instructor_id' => $this->instructor($request)->getKey()])->save();

        if ($jpeg !== null) {
            $this->images->attach($program, $jpeg);
        }

        // The program's page opens with the «تم إنشاء البرنامج» dialog (once).
        return redirect()->route('teach.programs.edit', $program)->with('programCreated', true);
    }

    public function edit(Request $request, Program $program): Response
    {
        $program = $this->ownProgram($request, $program);

        return Inertia::render('Teach/Programs/Form', [
            'item' => ProgramPresenter::forOwner($program->loadCount('classes')),
            'justCreated' => (bool) $request->session()->get('programCreated'),
        ]);
    }

    public function update(SaveProgramRequest $request, Program $program): RedirectResponse
    {
        $program = $this->ownProgram($request, $program);
        $jpeg = $this->renderImage($request);

        $program->fill($request->programData())->save();

        if ($jpeg !== null) {
            $this->images->attach($program, $jpeg);
        } elseif ($request->boolean('remove_image')) {
            $this->images->delete($program);
        }

        return back()->with('status', __('programs.saved'));
    }

    public function destroy(InstructorActionRequest $request, Program $program): RedirectResponse
    {
        $program = $this->ownProgram($request, $program);

        // Courses keep their program; remove them from it (or stop the program) first, as in rqmana.
        if ($program->classes()->exists()) {
            throw ValidationException::withMessages(['program' => __('programs.has_classes')]);
        }

        $this->images->delete($program);
        $program->delete();

        return redirect()->route('teach.programs.index')->with('status', __('programs.deleted'));
    }

    private function ownProgram(Request $request, Program $program): Program
    {
        abort_unless($program->instructor_id === $this->instructor($request)->getKey(), 404);

        return $program;
    }

    private function renderImage(SaveProgramRequest $request): ?string
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
}
