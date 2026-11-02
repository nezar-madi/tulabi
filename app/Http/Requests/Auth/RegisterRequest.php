<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ChecksRecaptcha;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\LibyanMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * A new account: name, phone, password, optional email. Also the account part of the
 * instructor application for someone who is not signed in.
 */
class RegisterRequest extends FormRequest
{
    use ChecksRecaptcha, NormalizesPhone;

    public function authorize(): bool
    {
        return $this->user('web') === null;
    }

    protected function prepareForValidation(): void
    {
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
        return self::accountRules();
    }

    /**
     * @return array<string, mixed>
     */
    public static function accountRules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'string', new LibyanMobile, Rule::unique('users', 'phone')],
            'email' => ['nullable', 'string', 'email:filter', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::accountMessages();
    }

    /**
     * @return array<string, string>
     */
    public static function accountMessages(): array
    {
        return [
            'phone.unique' => __('accounts.phone_taken'),
            'email.unique' => __('accounts.email_taken'),
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkRecaptcha($validator)];
    }
}
