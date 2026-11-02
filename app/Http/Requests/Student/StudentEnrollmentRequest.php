<?php

namespace App\Http\Requests\Student;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The student's own enrollment only.
 */
class StudentEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');
        $enrollment = $this->route('enrollment');

        return $user instanceof User
            && $enrollment instanceof Enrollment
            && $enrollment->user_id === $user->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
