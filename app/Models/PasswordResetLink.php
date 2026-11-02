<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-time link to set a new password (one hour, single use). Made only by
 * App\Support\PasswordResetLinks; nothing here is mass-assignable.
 */
class PasswordResetLink extends Model
{
    public const ISSUER_ADMIN = 'admin';

    public const ISSUER_INSTRUCTOR = 'instructor';

    public const ISSUER_EMAIL = 'email';

    protected $guarded = ['*'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<PasswordResetLink>  $query */
    public function scopeUsable(Builder $query): void
    {
        $query->whereNull('used_at')->where('expires_at', '>', now());
    }
}
