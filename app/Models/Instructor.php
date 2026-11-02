<?php

namespace App\Models;

use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The instructor side of an account, made when the admin accepts an application.
 * user_id and status are not mass-assignable.
 */
class Instructor extends Model
{
    /** @use HasFactory<InstructorFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'display_name',
        'specialty',
        'city',
        'bio',
        'whatsapp',
        'links',
    ];

    protected function casts(): array
    {
        return [
            'links' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
