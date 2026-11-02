<?php

namespace App\Support;

use App\Models\PhoneVerification;
use App\Support\Sms\SmsSender;

/**
 * Proving a phone number is the person's own before an account is made on it: a six-digit code
 * sent by message, valid for a few minutes, with a limited number of tries.
 */
class PhoneVerifications
{
    public const CODE_MINUTES = 10;

    public const RESEND_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly SmsSender $sms) {}

    /**
     * Send a new code. Returns the seconds to wait when one was sent a moment ago (nothing is sent then).
     */
    public function send(string $phone): int
    {
        $existing = PhoneVerification::query()->where('phone', $phone)->first();

        if ($existing !== null) {
            $wait = $this->secondsUntilResend($existing);

            if ($wait > 0) {
                return $wait;
            }
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $verification = $existing ?? new PhoneVerification;
        $verification->forceFill([
            'phone' => $phone,
            'code_hash' => self::hash($phone, $code),
            'attempts' => 0,
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(self::CODE_MINUTES),
        ])->save();

        $this->sms->send($phone, __('accounts.register.code_message', [
            'code' => $code,
            'minutes' => self::CODE_MINUTES,
        ]));

        return 0;
    }

    /**
     * Check a typed code. A wrong guess counts; after too many the code is void and a new one is needed.
     *
     * @return 'ok'|'wrong'|'expired'
     */
    public function check(string $phone, string $code): string
    {
        $verification = PhoneVerification::query()->where('phone', $phone)->first();

        if ($verification === null || $verification->expires_at->isPast() || $verification->attempts >= self::MAX_ATTEMPTS) {
            return 'expired';
        }

        $code = preg_replace('/\D/', '', LibyanPhone::westernDigits($code)) ?? '';

        if (hash_equals($verification->code_hash, self::hash($phone, $code))) {
            $verification->delete();

            return 'ok';
        }

        $verification->forceFill(['attempts' => $verification->attempts + 1])->save();

        return $verification->attempts >= self::MAX_ATTEMPTS ? 'expired' : 'wrong';
    }

    public function secondsUntilResend(PhoneVerification $verification): int
    {
        return max(0, (int) ceil($verification->sent_at->copy()->addSeconds(self::RESEND_SECONDS)->diffInSeconds(now(), false) * -1));
    }

    public function pendingWait(string $phone): ?int
    {
        $verification = PhoneVerification::query()->where('phone', $phone)->first();

        if ($verification === null || $verification->expires_at->isPast()) {
            return null;
        }

        return $this->secondsUntilResend($verification);
    }

    private static function hash(string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone.':'.$code, (string) config('app.key'));
    }
}
