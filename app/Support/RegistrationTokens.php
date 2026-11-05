<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * The app's sign-up has no session: once the code sent to a phone is right, the app gets a
 * short-lived, encrypted token that says "this phone was checked" and sends it back with the
 * password (as the website keeps `register.verified` in the session).
 */
class RegistrationTokens
{
    /** As long as the website keeps a checked phone. */
    public const MINUTES = 30;

    public function issue(string $phone): string
    {
        return Crypt::encryptString((string) json_encode([
            'purpose' => 'register',
            'phone' => $phone,
            'at' => now()->timestamp,
        ]));
    }

    /** The checked phone, or null when the token is not one of ours or is too old. */
    public function phone(mixed $token): ?string
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        try {
            $data = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($data) || ($data['purpose'] ?? null) !== 'register' || ! is_string($data['phone'] ?? null)) {
            return null;
        }

        if (now()->timestamp - (int) ($data['at'] ?? 0) > self::MINUTES * 60) {
            return null;
        }

        return $data['phone'];
    }
}
