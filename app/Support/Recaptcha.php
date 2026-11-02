<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Google reCAPTCHA v2 (checkbox) for public forms. The keys belong to the deployment
 * (RECAPTCHA_SITE_KEY / RECAPTCHA_SECRET_KEY).
 */
class Recaptcha
{
    public function siteKey(): string
    {
        return (string) config('services.recaptcha.site_key');
    }

    /**
     * Without keys a public form is refused in production; local
     * development and tests run without a captcha.
     */
    public function required(): bool
    {
        return (string) config('services.recaptcha.secret') !== '' || app()->environment('production');
    }

    public function verify(Request $request, mixed $token): bool
    {
        if (! $this->required()) {
            return true;
        }

        $secret = (string) config('services.recaptcha.secret');

        if ($secret === '' || ! is_string($token) || $token === '') {
            return false;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

        return $response->ok() && $response->json('success') === true;
    }
}
