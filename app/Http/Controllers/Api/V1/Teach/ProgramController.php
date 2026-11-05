<?php

namespace App\Http\Controllers\Api\V1\Teach;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teach\Concerns\FindsOwnClass;
use App\Http\Requests\Teach\InstructorActionRequest;
use App\Http\Requests\Teach\SaveProgramRequest;
use App\Models\Program;
use App\Support\Api\AppShape;
use App\Support\CourseContent;
use App\Support\Teach\ProgramEditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * «البرامج التدريبية» in the app: the list, one program, create, save, delete. Same rules as the
 * website (SaveProgramRequest validates, App\Support\Teach\ProgramEditor saves). A program with an
 * image is sent as multipart/form-data; saving an existing one with a new image goes by POST
 * (PHP reads files from POST only), plain JSON may use PUT.
 */
class ProgramController extends Controller
{
    use FindsOwnClass;

    public function __construct(private readonly ProgramEditor $programs) {}

    public function index(Request $request): JsonResponse
    {
        $instructor = $this->instructor($request);

        return response()->json([
            'programs' => AppShape::list($this->programs->list($instructor)),
            // Without a page address nothing is shown to visitors (the app says where to set it).
            'has_slug' => $instructor->slug !== null,
        ]);
    }

    /** The rules the form needs to check before sending (the server checks them again). */
    public function rules(): JsonResponse
    {
        return response()->json([
            'name' => ['min' => 3, 'max' => Program::NAME_MAX],
            'description' => ['min' => CourseContent::PROGRAM_DESCRIPTION_MIN, 'max' => CourseContent::DESCRIPTION_MAX],
            'lists' => ['names' => CourseContent::LISTS, 'max_items' => CourseContent::ITEMS_MAX, 'item_min' => CourseContent::PROGRAM_ITEM_MIN, 'item_max' => CourseContent::ITEM_MAX],
            'image' => ['types' => ['jpg', 'jpeg', 'png', 'webp'], 'max_kb' => 4096, 'min_width' => 600, 'min_height' => 300, 'max_side' => 6000],
            'video_hosts' => ['youtube.com', 'youtu.be', 'vimeo.com', 'dailymotion.com', 'dai.ly'],
        ]);
    }

    public function show(Request $request, Program $program): JsonResponse
    {
        return response()->json(['program' => AppShape::of($this->programs->show($this->ownProgram($request, $program)))]);
    }

    public function store(SaveProgramRequest $request): JsonResponse
    {
        $program = $this->programs->create($this->instructor($request), $request);

        return response()->json([
            'message' => __('programs.created'),
            'program' => AppShape::of($this->programs->show($program->fresh('instructor'))),
        ], 201);
    }

    public function update(SaveProgramRequest $request, Program $program): JsonResponse
    {
        $program = $this->programs->update($this->ownProgram($request, $program), $request);

        return response()->json([
            'message' => __('programs.saved'),
            'program' => AppShape::of($this->programs->show($program->fresh('instructor'))),
        ]);
    }

    public function destroy(InstructorActionRequest $request, Program $program): JsonResponse
    {
        $this->programs->delete($this->ownProgram($request, $program));

        return response()->json(['message' => __('programs.deleted')]);
    }
}
