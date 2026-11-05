<?php

namespace App\Http\Controllers\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\CancelClassRequest;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\ReactivateClassRequest;
use App\Http\Requests\Teach\SaveClassRequest;
use App\Models\TeachingClass;
use App\Support\ClassLifecycle;
use App\Support\ClassPresenter;
use App\Support\Teach\ClassEditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The instructor's classes. Every {class} is checked to be theirs (FormRequest first, ownClass()
 * second). The list, the form's data and saving are App\Support\Teach\ClassEditor, the actions
 * App\Support\ClassLifecycle (both shared with the app).
 */
class ClassController extends Controller
{
    use FindsOwnClass;

    public function __construct(private readonly ClassLifecycle $lifecycle, private readonly ClassEditor $classes) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Teach/Classes/Index', $this->classes->list($this->instructor($request), $request->query()));
    }

    public function create(Request $request): Response
    {
        // «فصل جديد من هذا البرنامج»: the programs page links here with the program chosen.
        return Inertia::render('Teach/Classes/Form', $this->classes->createData($this->instructor($request), $request->integer('program') ?: null));
    }

    public function store(SaveClassRequest $request): RedirectResponse
    {
        $class = $this->classes->create($this->instructor($request), $request);

        // The draft page opens with the «تم إنشاء الفصل» dialog (once).
        return redirect()->route('teach.classes.edit', $class)->with('classCreated', true);
    }

    public function edit(Request $request, TeachingClass $class): Response
    {
        $class = $this->ownClass($request, $class);

        return Inertia::render('Teach/Classes/Form', [
            ...$this->classes->editData($this->instructor($request), $class),
            'meetingChanged' => (bool) $request->session()->get('meetingChanged'),
            // After a save that moved the dates, hours or place of a class with students.
            'scheduleShareUrl' => $request->session()->get('scheduleChanged') ? ClassPresenter::scheduleShareUrl($class) : null,
            'justCreated' => (bool) $request->session()->get('classCreated'),
        ]);
    }

    public function update(SaveClassRequest $request, TeachingClass $class): RedirectResponse
    {
        $changed = $this->classes->update($this->ownClass($request, $class), $request);

        return back()->with('status', __('classes.saved'))
            ->with('meetingChanged', $changed['meeting_changed'])
            ->with('scheduleChanged', $changed['schedule_changed']);
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
        $class = $this->ownClass($request, $class);
        // Back to the list the class was in.
        $filter = $class->isCanceled() ? 'canceled' : ($class->status === TeachingClass::STATUS_DRAFT ? 'draft' : 'active');
        $wasDraft = $class->status === TeachingClass::STATUS_DRAFT;

        $this->lifecycle->delete($class);

        return redirect()->route('teach.classes.index', ['filter' => $filter])
            ->with('status', __($wasDraft ? 'classes.deleted' : 'classes.deleted_class'));
    }
}
