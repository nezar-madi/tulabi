<?php

namespace App\Http\Requests\Teach;

use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Instructor-area requests: an active instructor, and for a {class} route, only their own class.
 */
abstract class InstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $instructor = $this->instructor();

        if ($instructor === null) {
            return false;
        }

        $class = $this->route('class');

        return ! $class instanceof TeachingClass || $class->instructor_id === $instructor->getKey();
    }

    public function instructor(): ?Instructor
    {
        $user = $this->user();

        return $user instanceof User && $user->isInstructor() ? $user->instructor : null;
    }
}
