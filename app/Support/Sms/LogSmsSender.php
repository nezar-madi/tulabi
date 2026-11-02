<?php

namespace App\Support\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Writes the message to the log instead of sending it. For local work only: production
 * must use a real gateway (see config/tulabi.php).
 */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $text): void
    {
        Log::info('SMS to '.$phone.': '.$text);
    }
}
