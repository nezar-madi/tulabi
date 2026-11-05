<?php

namespace App\Support;

use App\Models\Instructor;
use App\Models\Program;
use App\Models\TeachingClass;
use Illuminate\Database\Eloquent\Collection;

/**
 * What visitors may see of an instructor: their page, open classes and published programs.
 * Shared by the public website pages (InstructorPageController) and the app (/api/v1/instructors),
 * so both apply the same rules. The owner also sees their drafts (the website's preview).
 */
class PublicCatalog
{
    /** An instructor with a public page (active, with an address, account not suspended). */
    public function instructor(string $slug): Instructor
    {
        return Instructor::query()->public()->where('slug', $slug)->with('user')->firstOrFail();
    }

    /**
     * Published or closed classes that have not ended, soonest first.
     *
     * @return Collection<int, TeachingClass>
     */
    public function openClasses(Instructor $instructor): Collection
    {
        $today = TulabiClock::today();

        return $instructor->classes()
            ->with('program')
            ->publiclyVisible()
            ->where(fn ($query) => $query
                ->where('ends_on', '>=', $today)
                ->orWhere(fn ($query) => $query->whereNull('ends_on')->where('starts_on', '>=', $today)))
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();
    }

    /**
     * Published, active programs: the featured ones first, each with its number of open courses.
     *
     * @return list<array<string, mixed>>
     */
    public function publicPrograms(Instructor $instructor, ?int $limit = null): array
    {
        $open = $this->openClasses($instructor)->countBy('program_id');

        return $instructor->programs()
            ->public()
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get()
            ->map(fn (Program $program) => InstructorSite::program($program, $instructor, (int) ($open[$program->id] ?? 0)))
            ->all();
    }

    /** One class: visitors see it until it ends; the owner also sees drafts and ended ones (not canceled). */
    public function visibleClass(Instructor $instructor, int $id, bool $isOwner = false): TeachingClass
    {
        $today = TulabiClock::today();

        return $instructor->classes()
            ->whereKey($id)
            ->when(! $isOwner, fn ($query) => $query->publiclyVisible()->where(fn ($query) => $query
                ->where('ends_on', '>=', $today)
                ->orWhere(fn ($query) => $query->whereNull('ends_on')->where('starts_on', '>=', $today))))
            ->when($isOwner, fn ($query) => $query->where('status', '!=', TeachingClass::STATUS_CANCELED))
            ->firstOrFail();
    }

    /** One program: visitors see a public one only; the owner sees any of theirs. */
    public function visibleProgram(Instructor $instructor, int $id, bool $isOwner = false): Program
    {
        return $instructor->programs()
            ->whereKey($id)
            ->when(! $isOwner, fn ($query) => $query->public())
            ->firstOrFail();
    }
}
