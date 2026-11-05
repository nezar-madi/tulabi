<?php

namespace App\Http\Requests\Student;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * «اطلب الانضمام» (the website, or the app's /api/v1/classes/{class}/join). Whether the class
 * takes requests is checked by App\Support\Enrollments.
 */
class JoinClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isActive();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
