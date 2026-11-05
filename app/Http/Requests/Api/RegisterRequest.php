<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * The last step: the token from the code check, a password, and the device's name.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'registration_token' => ['required', 'string', 'max:2000'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }
}
