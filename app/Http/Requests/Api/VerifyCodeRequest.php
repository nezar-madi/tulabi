<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\LibyanMobile;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The second step: the phone again (the app keeps no session) and the code sent to it.
 */
class VerifyCodeRequest extends FormRequest
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
            'phone' => ['required', 'string', new LibyanMobile],
            'code' => ['required', 'string', 'max:20'],
        ];
    }
}
