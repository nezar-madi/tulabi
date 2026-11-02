<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ChecksRecaptcha;
use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * «انضم كمدرّس». Someone signed in only adds the teaching details; a visitor also makes
 * their account in the same form (name, phone, password, optional email) and passes the captcha.
 */
class InstructorApplicationRequest extends FormRequest
{
    use ChecksRecaptcha, NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user('web') !== null) {
            return;
        }

        $this->normalizePhoneInput();

        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $application = [
            'specialty' => ['required', 'string', 'min:2', 'max:120'],
            'city' => ['required', 'string', 'min:2', 'max:80'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ];

        return $this->user('web') === null
            ? [...RegisterRequest::accountRules(), ...$application]
            : $application;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return RegisterRequest::accountMessages();
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return $this->user('web') === null
            ? [fn (Validator $validator) => $this->checkRecaptcha($validator)]
            : [];
    }
}
