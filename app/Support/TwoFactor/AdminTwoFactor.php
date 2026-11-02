<?php

namespace App\Support\TwoFactor;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

/**
 * Second factor for admin accounts (copied from rqmana's PanelTwoFactor): authenticator codes plus one-time recovery codes.
 */
class AdminTwoFactor
{
    public const RECOVERY_CODE_COUNT = 8;

    /** No 0/O or 1/I so codes read back without mistakes. */
    private const RECOVERY_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function issuer(): string
    {
        $name = trim((string) config('app.name'));

        return $name !== '' ? $name : 'طلابي';
    }

    /**
     * Accept an authenticator code once: the step is stored atomically, so the same
     * code (or an older one) can't be used again, even by two requests at once.
     */
    public function verifyCode(Admin $admin, string $code): bool
    {
        $secret = $admin->two_factor_secret;
        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $step = Totp::verify($secret, $code, $admin->two_factor_last_step);
        if ($step === null) {
            return false;
        }

        $updated = Admin::query()
            ->whereKey($admin->getKey())
            ->where(fn ($query) => $query
                ->whereNull('two_factor_last_step')
                ->orWhere('two_factor_last_step', '<', $step))
            ->update(['two_factor_last_step' => $step]);

        if ($updated !== 1) {
            return false;
        }

        $admin->forceFill(['two_factor_last_step' => $step])->syncOriginalAttribute('two_factor_last_step');

        return true;
    }

    /**
     * Use a recovery code: true once per code, then it is gone.
     */
    public function useRecoveryCode(Admin $admin, string $code): bool
    {
        $hash = self::hashRecoveryCode($code);
        if ($hash === null) {
            return false;
        }

        return DB::connection($admin->getConnectionName())->transaction(function () use ($admin, $hash) {
            /** @var Admin|null $fresh */
            $fresh = Admin::query()->whereKey($admin->getKey())->lockForUpdate()->first();
            if ($fresh === null) {
                return false;
            }

            $hashes = $fresh->recoveryCodeHashes();
            $remaining = [];
            $found = false;
            foreach ($hashes as $stored) {
                if (! $found && hash_equals($stored, $hash)) {
                    $found = true;

                    continue;
                }
                $remaining[] = $stored;
            }

            if (! $found) {
                return false;
            }

            $fresh->forceFill(['two_factor_recovery_codes' => json_encode($remaining)])->save();
            $admin->setRawAttributes($fresh->getAttributes(), true);

            return true;
        });
    }

    /**
     * Turn the second factor on with a confirmed secret. Returns the plain recovery codes
     * (shown once; only their hashes are kept).
     *
     * @return list<string>
     */
    public function enable(Admin $admin, string $secret, int $confirmedStep): array
    {
        $codes = $this->generateRecoveryCodes();

        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode(array_map(self::hashRecoveryCode(...), $codes)),
            'two_factor_last_step' => $confirmedStep,
        ])->save();

        return $codes;
    }

    /**
     * @return list<string>
     */
    public function regenerateRecoveryCodes(Admin $admin): array
    {
        $codes = $this->generateRecoveryCodes();

        $admin->forceFill([
            'two_factor_recovery_codes' => json_encode(array_map(self::hashRecoveryCode(...), $codes)),
        ])->save();

        return $codes;
    }

    public function disable(Admin $admin): void
    {
        $admin->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_last_step' => null,
        ])->save();
    }

    /**
     * @return list<string> codes like ABCD-EFGH
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        $max = strlen(self::RECOVERY_ALPHABET) - 1;

        while (count($codes) < self::RECOVERY_CODE_COUNT) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::RECOVERY_ALPHABET[random_int(0, $max)];
            }
            $codes[substr($code, 0, 4).'-'.substr($code, 4)] = true;
        }

        return array_keys($codes);
    }

    public static function hashRecoveryCode(string $code): ?string
    {
        $normalized = strtoupper((string) preg_replace('/[\s-]+/', '', $code));
        if (preg_match('/^['.self::RECOVERY_ALPHABET.']{8}$/', $normalized) !== 1) {
            return null;
        }

        return hash('sha256', $normalized);
    }
}
