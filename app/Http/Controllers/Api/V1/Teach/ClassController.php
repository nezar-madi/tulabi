<?php

namespace App\Http\Controllers\Api\V1\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\CancelClassRequest;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\ReactivateClassRequest;
use App\Http\Requests\Teach\SaveClassRequest;
use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Support\Api\AppShape;
use App\Support\ClassLifecycle;
use App\Support\ClassPresenter;
use App\Support\Teach\ClassEditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The instructor's classes in the app: the list, the form's data, create, save, and the actions
 * (publish, close, reopen a suspended class on a new date, cancel, delete). Same rules as the
 * website (SaveClassRequest, App\Support\Teach\ClassEditor, App\Support\ClassLifecycle). Each
 * answer brings the class back as its edit screen shows it, so the app never guesses its new state.
 */
class ClassController extends Controller
{
    use FindsOwnClass;

    public function __construct(private readonly ClassLifecycle $lifecycle, private readonly ClassEditor $classes) {}

    /** ?filter=active|draft|canceled, q, when=upcoming|running|ended, from, to, price=paid|free, type, program=none|id, page. */
    public function index(Request $request): JsonResponse
    {
        $data = $this->plain($this->classes->list($this->instructor($request), $request->query()));

        // The narrowing in use, always an object (an empty PHP array would be a JSON list).
        return response()->json([...$data, 'narrow' => (object) $data['narrow']]);
    }

    /** What the new-class form needs (?program= chooses a program in advance). */
    public function form(Request $request): JsonResponse
    {
        return response()->json($this->plain($this->classes->createData($this->instructor($request), $request->integer('program') ?: null)));
    }

    public function show(Request $request, TeachingClass $class): JsonResponse
    {
        return response()->json($this->edit($request, $this->ownClass($request, $class)));
    }

    public function store(SaveClassRequest $request): JsonResponse
    {
        $class = $this->classes->create($this->instructor($request), $request);

        return response()->json(['message' => __('classes.created'), ...$this->edit($request, $class->fresh())], 201);
    }

    public function update(SaveClassRequest $request, TeachingClass $class): JsonResponse
    {
        $class = $this->ownClass($request, $class);
        $changed = $this->classes->update($class, $request);
        $class->refresh();

        return response()->json([
            'message' => __('classes.saved'),
            // A new meeting link or new dates for a class with students: offer to send it on WhatsApp.
            'meeting_changed' => $changed['meeting_changed'],
            'schedule_changed' => $changed['schedule_changed'],
            'schedule_share_url' => $changed['schedule_changed'] ? ClassPresenter::scheduleShareUrl($class) : null,
            ...$this->edit($request, $class),
        ]);
    }

    public function publish(InstructorActionRequest $request, TeachingClass $class): JsonResponse
    {
        $class = $this->ownClass($request, $class);
        $this->lifecycle->publish($class);

        return $this->after($request, $class, __('classes.published'));
    }

    public function close(InstructorActionRequest $request, TeachingClass $class): JsonResponse
    {
        $class = $this->ownClass($request, $class);
        $this->lifecycle->close($class);

        return $this->after($request, $class, __('classes.closed'));
    }

    /** A suspended class starts again on a new date (`starts_on`). */
    public function reactivate(ReactivateClassRequest $request, TeachingClass $class): JsonResponse
    {
        $class = $this->ownClass($request, $class);
        $this->lifecycle->reactivate($class, (string) $request->validated('starts_on'));
        $class->refresh();

        return response()->json([
            'message' => __('classes.reactivated'),
            'schedule_changed' => $class->hasAcceptedStudents(),
            'schedule_share_url' => $class->hasAcceptedStudents() ? ClassPresenter::scheduleShareUrl($class) : null,
            ...$this->edit($request, $class),
        ]);
    }

    public function cancel(CancelClassRequest $request, TeachingClass $class): JsonResponse
    {
        $class = $this->ownClass($request, $class);
        $this->lifecycle->cancel($class, $request->validated('reason'));

        return $this->after($request, $class, __('classes.canceled'));
    }

    public function destroy(InstructorActionRequest $request, TeachingClass $class): JsonResponse
    {
        $class = $this->ownClass($request, $class);
        $wasDraft = $class->status === TeachingClass::STATUS_DRAFT;
        $this->lifecycle->delete($class);

        return response()->json(['message' => __($wasDraft ? 'classes.deleted' : 'classes.deleted_class')]);
    }

    private function after(Request $request, TeachingClass $class, string $message): JsonResponse
    {
        return response()->json(['message' => $message, ...$this->edit($request, $class->refresh())]);
    }

    /**
     * The class as its edit screen shows it.
     *
     * @return array<string, mixed>
     */
    private function edit(Request $request, TeachingClass $class): array
    {
        /** @var Instructor $instructor */
        $instructor = $this->instructor($request);

        return $this->plain($this->classes->editData($instructor, $class));
    }

    /**
     * Collections and paginators as plain arrays, with full addresses.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function plain(array $data): array
    {
        return AppShape::of(json_decode((string) json_encode($data), true));
    }
}
