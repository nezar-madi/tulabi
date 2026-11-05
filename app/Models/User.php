<?php

namespace App\Models;

use App\Support\LibyanPhone;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * An account starts with only a phone. Until the person fills in their name, the phone stands
     * in for it wherever a name is shown (lists, menus); use hasName() for the real value.
     *
     * @return Attribute<string, string|null>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (?string $value) => $value !== null && trim($value) !== ''
            ? $value
            : (string) LibyanPhone::display($this->phone));
    }

    public function hasName(): bool
    {
        return trim((string) $this->getRawOriginal('name')) !== '';
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

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
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
