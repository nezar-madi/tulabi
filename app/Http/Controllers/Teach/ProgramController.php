<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\SaveProgramRequest;
use App\Models\Program;
use App\Support\Teach\ProgramEditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * «البرامج التدريبية» of the instructor. Every {program} is checked to be theirs (FormRequest
 * first, ownProgram() second). The work is App\Support\Teach\ProgramEditor (shared with the app).
 */
class ProgramController extends Controller
{
    use FindsOwnClass;

    public function __construct(private readonly ProgramEditor $programs) {}

    public function index(Request $request): Response
    {
        $instructor = $this->instructor($request);

        return Inertia::render('Teach/Programs/Index', [
            'programs' => $this->programs->list($instructor),
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
        $program = $this->programs->create($this->instructor($request), $request);

        // The program's page opens with the «تم إنشاء البرنامج» dialog (once).
        return redirect()->route('teach.programs.edit', $program)->with('programCreated', true);
    }

    public function edit(Request $request, Program $program): Response
    {
        return Inertia::render('Teach/Programs/Form', [
            'item' => $this->programs->show($this->ownProgram($request, $program)),
            'justCreated' => (bool) $request->session()->get('programCreated'),
        ]);
    }

    public function update(SaveProgramRequest $request, Program $program): RedirectResponse
    {
        $this->programs->update($this->ownProgram($request, $program), $request);

        return back()->with('status', __('programs.saved'));
    }

    public function destroy(InstructorActionRequest $request, Program $program): RedirectResponse
    {
        $this->programs->delete($this->ownProgram($request, $program));

        return redirect()->route('teach.programs.index')->with('status', __('programs.deleted'));
    }
}
