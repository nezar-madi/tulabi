<?php

namespace App\Support;

/**
 * wa.me links with a ready message. Tulabi sends nothing itself (no SMS, no WhatsApp API):
 * the person clicks the button and sends the message from their own WhatsApp.
 */
class WhatsApp
{
    public static function link(?string $phone, string $text = ''): ?string
    {
        $number = LibyanPhone::forWhatsApp($phone);

        if ($number === null) {
            return null;
        }

        return 'https://wa.me/'.$number.($text !== '' ? '?text='.rawurlencode($text) : '');
    }

    /** No number: WhatsApp asks whom to send it to (a contact or the class's group). */
    public static function share(string $text): string
    {
        return 'https://wa.me/?text='.rawurlencode($text);
    }
}
