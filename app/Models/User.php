<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * One account per phone number. phone, password and status are not mass-assignable:
 * set them with forceFill()/forceCreate() and explicit values only.
 *
 * @property string $phone E.164, from App\Support\LibyanPhone
 * @property string $status active|suspended
 */
#[Fillable(['name', 'email'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return HasOne<Instructor, $this> */
    public function instructor(): HasOne
    {
        return $this->hasOne(Instructor::class);
    }

    /** @return HasMany<InstructorApplication, $this> */
    public function instructorApplications(): HasMany
    {
        return $this->hasMany(InstructorApplication::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** An accepted instructor whose teaching is not switched off. */
    public function isInstructor(): bool
    {
        return $this->instructor !== null && $this->instructor->status === Instructor::STATUS_ACTIVE;
    }

    /** The latest application that is still waiting for the admin, if any. */
    public function pendingInstructorApplication(): ?InstructorApplication
    {
        return $this->instructorApplications()
            ->where('status', InstructorApplication::STATUS_PENDING)
            ->latest('id')
            ->first();
    }
}
