<?php

namespace App\Http\Controllers\Teach\Concerns;

use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Http\Request;

trait FindsOwnClass
{
    protected function instructor(Request $request): Instructor
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isInstructor(), 403);

        return $user->instructor;
    }

    /**
     * Second layer behind the FormRequest: another instructor's class is "not found".
     */
    protected function ownClass(Request $request, TeachingClass $class): TeachingClass
    {
        abort_unless($class->instructor_id === $this->instructor($request)->getKey(), 404);

        return $class;
    }
}
