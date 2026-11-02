<?php

namespace App\Http\Requests\Teach;

use App\Models\Enrollment;

/**
 * Accept, reject, remove, reset link: only for enrollments in the instructor's own classes.
 */
class EnrollmentActionRequest extends InstructorRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');
        $instructor = $this->instructor();

        return parent::authorize()
            && $enrollment instanceof Enrollment
            && $enrollment->teachingClass?->instructor_id === $instructor?->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
