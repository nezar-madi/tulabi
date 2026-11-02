<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ChecksRecaptcha;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\LibyanMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The first sign-up step: a Libyan mobile with no account yet. A code is sent to it.
 */
class SendRegisterCodeRequest extends FormRequest
{
    use ChecksRecaptcha, NormalizesPhone;

    public function authorize(): bool
    {
        return $this->user('web') === null;
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

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkRecaptcha($validator)];
    }
}
