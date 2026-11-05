<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Auth\LoginRequest as WebLoginRequest;

/**
 * The app's sign-in: phone and password as on the website, and the device's name, shown
 * later in a list of signed-in devices.
 */
class LoginRequest extends WebLoginRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }
}
