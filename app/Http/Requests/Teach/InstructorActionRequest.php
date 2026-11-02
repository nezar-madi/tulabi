<?php

namespace App\Http\Requests\Teach;

/**
 * An instructor action without input (publish, close, delete, remove the photo).
 */
class InstructorActionRequest extends InstructorRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
