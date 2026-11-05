<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * «انضم كمدرّس». Only someone signed in applies (a visitor signs up by phone first); the form
 * asks for the full name too when the profile has none yet, since the instructor page uses it.
 */
class InstructorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'specialty' => ['required', 'string', 'min:2', 'max:120'],
            'city' => ['required', 'string', 'min:2', 'max:80'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ];

        if (! $this->user()->hasName()) {
            $rules['name'] = ['required', 'string', 'min:3', 'max:120'];
        }

        return $rules;
    }
}
