<?php

namespace App\Support\TwoFactor;

use InvalidArgumentException;

/**
 * Time-based one-time passwords (RFC 6238 over RFC 4226): HMAC-SHA1, 30-second steps,
 * 6 digits, base32 secrets as authenticator apps expect them.
 */
class Totp
{
    public const PERIOD = 30;

    public const DIGITS = 6;

    /** Steps accepted on either side of the current one (clock drift). */
    public const WINDOW = 1;

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * A new base32 secret made from 20 random bytes (160 bits, the RFC 4226 recommendation).
     */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function stepAt(int $timestamp): int
    {
        return intdiv($timestamp, self::PERIOD);
    }

    /**
     * The code for a raw (binary) key at a time step.
     */
    public static function codeForStep(string $key, int $step, int $digits = self::DIGITS): string
    {
        $counter = pack('J', $step);
        $hash = hash_hmac('sha1', $counter, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($binary % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * The current code for a base32 secret.
     */
    public static function code(string $secret, ?int $timestamp = null): string
    {
        return self::codeForStep(self::base32Decode($secret), self::stepAt($timestamp ?? now()->getTimestamp()));
    }

    /**
     * Check a code against the current step ± WINDOW, skipping steps at or before $lastStep
     * (already used). Returns the matched step, or null.
     */
    public static function verify(string $secret, string $code, ?int $lastStep = null, ?int $timestamp = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (preg_match('/^\d{'.self::DIGITS.'}$/', $code) !== 1) {
            return null;
        }

        try {
            $key = self::base32Decode($secret);
        } catch (InvalidArgumentException) {
            return null;
        }

        $current = self::stepAt($timestamp ?? now()->getTimestamp());
        $matched = null;

        // Compare every candidate in constant time; don't stop early.
        for ($step = $current - self::WINDOW; $step <= $current + self::WINDOW; $step++) {
            $ok = hash_equals(self::codeForStep($key, $step), $code);
            if ($ok && $matched === null && ($lastStep === null || $step > $lastStep)) {
                $matched = $step;
            }
        }

        return $matched;
    }

    /**
     * The otpauth:// link authenticator apps open (Key URI format).
     */
    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer).':'.rawurlencode($account);

        return 'otpauth://totp/'.$label.'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::BASE32_ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $data === '' ? '' : $output;
    }

    public static function base32Decode(string $secret): string
    {
        $secret = strtoupper((string) preg_replace('/[\s=-]+/', '', $secret));
        if ($secret === '') {
            throw new InvalidArgumentException('Empty base32 secret.');
        }

        $bits = '';
        foreach (str_split($secret) as $char) {
            $index = strpos(self::BASE32_ALPHABET, $char);
            if ($index === false) {
                throw new InvalidArgumentException('Invalid base32 character.');
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }
}
