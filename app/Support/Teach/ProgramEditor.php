<?php

namespace App\Support\Teach;

use App\Http\Requests\Teach\SaveProgramRequest;
use App\Models\Instructor;
use App\Models\Program;
use App\Support\ClassImage;
use App\Support\ProgramPresenter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * «البرامج التدريبية»: listing, creating, saving and deleting an instructor's programs. Shared by
 * the website's Teach\ProgramController and the app's /api/v1/teach/programs. The callers check
 * that a program is the instructor's own (SaveProgramRequest, then FindsOwnClass::ownProgram).
 */
class ProgramEditor
{
    public function __construct(private readonly ClassImage $images) {}

    /**
     * The instructor's programs, featured first, each with its number of classes.
     *
     * @return list<array<string, mixed>>
     */
    public function list(Instructor $instructor): array
    {
        return $instructor->programs()
            ->with('instructor')
            ->withCount('classes')
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get()
            ->map(fn (Program $program) => ProgramPresenter::forOwner($program))
            ->values()
            ->all();
    }

    /**
     * One program as its edit form shows it.
     *
     * @return array<string, mixed>
     */
    public function show(Program $program): array
    {
        return ProgramPresenter::forOwner($program->loadCount('classes'));
    }

    public function create(Instructor $instructor, SaveProgramRequest $request): Program
    {
        $jpeg = $this->renderImage($request);

        return $this->unlessNameTaken(fn () => DB::transaction(function () use ($instructor, $request, $jpeg) {
            $program = new Program($request->programData());
            $program->forceFill(['instructor_id' => $instructor->getKey()])->save();

            if ($jpeg !== null) {
                $this->images->attach($program, $jpeg);
            }

            return $program;
        }));
    }

    public function update(Program $program, SaveProgramRequest $request): Program
    {
        $jpeg = $this->renderImage($request);

        return $this->unlessNameTaken(fn () => DB::transaction(function () use ($request, $program, $jpeg) {
            $program->fill($request->programData())->save();

            if ($jpeg !== null) {
                $this->images->attach($program, $jpeg);
            } elseif ($request->boolean('remove_image')) {
                $this->images->delete($program);
            }

            return $program;
        }));
    }

    /**
     * Courses keep their program; remove them from it (or stop the program) first, as in rqmana.
     * The check and the delete run together (the row locked), so a course added meanwhile is not
     * silently left without its program; the image file goes only once the row is gone.
     */
    public function delete(Program $program): void
    {
        DB::transaction(function () use ($program) {
            Program::query()->whereKey($program->getKey())->lockForUpdate()->first();

            if ($program->classes()->exists()) {
                throw ValidationException::withMessages(['program' => __('programs.has_classes')]);
            }

            $program->delete();
        });

        if ($program->image_path !== null) {
            Storage::disk('public')->delete($program->image_path);
        }
    }

    /**
     * Two saves with the same name at the same moment: the second one hits the database's unique
     * index; tell the instructor as the validation would, not with a server error.
     *
     * @template T
     *
     * @param  callable(): T  $save
     * @return T
     */
    private function unlessNameTaken(callable $save): mixed
    {
        try {
            return $save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => __('programs.name_taken')]);
        }
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
