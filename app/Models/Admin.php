<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * Admin accounts (guard "admin"), separate from users. Every admin must set up an
 * authenticator before using the admin pages (EnsureAdminTwoFactor).
 *
 * @property string|null $two_factor_secret
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $two_factor_recovery_codes
 * @property int|null $two_factor_last_step
 */
class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_last_step',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted',
            'two_factor_last_step' => 'integer',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null
            && is_string($this->two_factor_secret)
            && $this->two_factor_secret !== '';
    }

    /**
     * @return list<string>
     */
    public function recoveryCodeHashes(): array
    {
        $decoded = json_decode((string) $this->two_factor_recovery_codes, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }
}
