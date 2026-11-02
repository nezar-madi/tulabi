<?php

namespace Tests\Unit;

use App\Support\LibyanPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LibyanPhoneTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function sameNumberWrittenManyWays(): array
    {
        return [
            'local with 0' => ['0912345678', '+218912345678'],
            'local without 0' => ['912345678', '+218912345678'],
            'international with +' => ['+218912345678', '+218912345678'],
            'international with 00' => ['00218912345678', '+218912345678'],
            'spaces and dashes' => ['+218 91-234-5678', '+218912345678'],
            'local with dash' => ['091-2345678', '+218912345678'],
            'Arabic-Indic digits' => ['٠٩١٢٣٤٥٦٧٨', '+218912345678'],
            'surrounding spaces' => ['  0921234567 ', '+218921234567'],
            'Almadar 091' => ['0911234567', '+218911234567'],
            'Libyana 092' => ['0921234567', '+218921234567'],
            'Libyana 094' => ['0941234567', '+218941234567'],
        ];
    }

    #[DataProvider('sameNumberWrittenManyWays')]
    public function test_it_normalizes_libyan_mobiles_to_e164(string $input, string $expected): void
    {
        $this->assertSame($expected, LibyanPhone::normalize($input));
        $this->assertTrue(LibyanPhone::isValid($input));
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function notALibyanMobile(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'letters' => ['phone'],
            'too short' => ['091234'],
            'too long' => ['09123456789'],
            'Tripoli landline' => ['0213334444'],
            'Egyptian mobile' => ['+201001234567'],
            'Tunisian mobile' => ['+21620123456'],
        ];
    }

    #[DataProvider('notALibyanMobile')]
    public function test_it_rejects_anything_else(?string $input): void
    {
        $this->assertNull(LibyanPhone::normalize($input));
        $this->assertFalse(LibyanPhone::isValid($input));
    }

    public function test_whatsapp_and_display_forms(): void
    {
        $this->assertSame('218912345678', LibyanPhone::forWhatsApp('0912345678'));
        $this->assertSame('091-2345678', LibyanPhone::display('+218912345678'));
        $this->assertNull(LibyanPhone::forWhatsApp('123'));
        $this->assertNull(LibyanPhone::display('123'));
    }
}
