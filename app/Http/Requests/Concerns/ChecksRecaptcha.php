<?php

namespace App\Http\Requests\Concerns;

use App\Support\Recaptcha;
use Illuminate\Validation\Validator;

/**
 * Public sign-up forms: the reCAPTCHA must pass (skipped locally when no keys are set).
 */
trait ChecksRecaptcha
{
    protected function checkRecaptcha(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        if (! app(Recaptcha::class)->verify($this, $this->input('g-recaptcha-response'))) {
            $validator->errors()->add('g-recaptcha-response', __('accounts.captcha'));
        }
    }
}
