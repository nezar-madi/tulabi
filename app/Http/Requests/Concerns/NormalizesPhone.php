<?php

namespace App\Http\Requests\Concerns;

use App\Support\LibyanPhone;

/**
 * Turn the typed phone into E.164 before validation, so "0912345678" and "+218912345678"
 * are the same account. An invalid number is left as typed for the LibyanMobile rule.
 */
trait NormalizesPhone
{
    protected function normalizePhoneInput(string $field = 'phone'): void
    {
        $value = $this->input($field);

        if (is_string($value)) {
            $this->merge([$field => LibyanPhone::normalize($value) ?? trim($value)]);
        }
    }
}
