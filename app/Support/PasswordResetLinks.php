<?php

namespace App\Support;

use App\Models\PasswordResetLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-time links to set a new password. Without SMS this is how people get back in:
 * the admin (for anyone) or an instructor (for their students) makes a link and sends it
 * with a WhatsApp button; people with an email get one by email. A link lasts one hour,
 * works once, and making a new one cancels the older unused ones.
 */
class PasswordResetLinks
{
    public const LIFETIME_MINUTES = 60;

    /**
     * Make a link for the user and return its full URL (the token is not stored, only its hash).
     */
    public function issue(User $user, string $issuerType, ?int $issuerId = null): string
    {
        $token = Str::random(48);

        DB::transaction(function () use ($user, $issuerType, $issuerId, $token) {
            PasswordResetLink::query()
                ->where('user_id', $user->getKey())
                ->whereNull('used_at')
                ->delete();

            $link = new PasswordResetLink;
            $link->forceFill([
                'user_id' => $user->getKey(),
                'token_hash' => self::hash($token),
                'issued_by_type' => $issuerType,
                'issued_by_id' => $issuerId,
                'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES),
            ])->save();
        });

        return route('password.reset', ['token' => $token]);
    }

    /**
     * The usable link for a token, or null (unknown, used, expired, or the account is suspended).
     */
    public function find(string $token): ?PasswordResetLink
    {
        if (strlen($token) !== 48) {
            return null;
        }

        $link = PasswordResetLink::query()
            ->usable()
            ->where('token_hash', self::hash($token))
            ->with('user')
            ->first();

        return $link !== null && $link->user?->isActive() ? $link : null;
    }

    /**
     * Set the new password and use the link up. Returns the user, or null when the link
     * is no longer usable (two submits at once: only one wins).
     */
    public function consume(string $token, string $password): ?User
    {
        $link = $this->find($token);

        if ($link === null) {
            return null;
        }

        return DB::transaction(function () use ($link, $password) {
            $claimed = PasswordResetLink::query()
                ->whereKey($link->getKey())
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            if ($claimed !== 1) {
                return null;
            }

            $user = $link->user;
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            // The app's sign-ins end too (the website's end through AuthenticateSession).
            $user->tokens()->delete();

            return $user;
        });
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
