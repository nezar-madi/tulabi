<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Site-wide settings the admin can change (key/value).
 */
class Setting extends Model
{
    /** Where new instructor applications are announced (empty = nobody). */
    public const APPLICATIONS_NOTIFY_EMAIL = 'applications_notify_email';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->find($key)?->value ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
