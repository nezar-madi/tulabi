<?php

namespace App\Rules;

use App\Support\LibyanPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LibyanMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! LibyanPhone::isValid($value)) {
            $fail(__('validation.custom.phone.libyan_mobile'));
        }
    }
}
