<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * Libyan mobile numbers, the only kind tulabi accepts.
 *
 * A number is stored in E.164 form (+2189XXXXXXXX), which is what makes "one account per
 * phone" work: 0912345678, 912345678, 00218912345678 and +218 91-234-5678 are one person.
 */
class LibyanPhone
{
    public const REGION = 'LY';

    /**
     * The E.164 form of a Libyan mobile number, or null when the input is not one.
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $input = self::westernDigits(trim($input));

        if ($input === '') {
            return null;
        }

        // "00218..." is the international prefix written out; libphonenumber reads it
        // from LY only as an exit code it does not know, so turn it into "+218...".
        if (str_starts_with($input, '00')) {
            $input = '+'.substr($input, 2);
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $number = $util->parse($input, self::REGION);
        } catch (NumberParseException) {
            return null;
        }

        if ($util->getRegionCodeForNumber($number) !== self::REGION || ! $util->isValidNumber($number)) {
            return null;
        }

        if ($util->getNumberType($number) !== PhoneNumberType::MOBILE) {
            return null;
        }

        return $util->format($number, PhoneNumberFormat::E164);
    }

    public static function isValid(?string $input): bool
    {
        return self::normalize($input) !== null;
    }

    /**
     * The number for a wa.me link: digits only, with the country code (2189XXXXXXXX).
     */
    public static function forWhatsApp(?string $input): ?string
    {
        $e164 = self::normalize($input);

        return $e164 === null ? null : ltrim($e164, '+');
    }

    /**
     * The local way Libyans write it (091-2345678), for display.
     */
    public static function display(?string $input): ?string
    {
        $e164 = self::normalize($input);

        if ($e164 === null) {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();

        return $util->format($util->parse($e164, self::REGION), PhoneNumberFormat::NATIONAL);
    }

    /** Arabic-Indic and Persian digits typed on Arabic keyboards become 0-9. */
    public static function westernDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}
