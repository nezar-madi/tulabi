<?php

namespace App\Support\Sms;

/**
 * Sends a short text message to a phone (E.164). The driver is chosen by config tulabi.sms.driver.
 */
interface SmsSender
{
    public function send(string $phone, string $text): void;
}
