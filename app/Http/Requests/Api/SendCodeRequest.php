<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\LibyanMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The app's first sign-up step: a Libyan mobile with no account yet (as the website's
 * SendRegisterCodeRequest, without the website's reCAPTCHA; the throttle:register limits apply).
 */
class SendCodeRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new LibyanMobile, Rule::unique('users', 'phone')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone.unique' => __('accounts.phone_taken')];
    }
}
