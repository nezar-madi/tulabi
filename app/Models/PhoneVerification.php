<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The sign-up code sent to a phone (App\Support\PhoneVerifications). Never mass-assigned.
 *
 * @property string $phone
 * @property string $code_hash
 * @property int $attempts
 */
class PhoneVerification extends Model
{
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
