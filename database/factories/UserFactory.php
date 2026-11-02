<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+2189'.fake()->randomElement(['1', '2', '4']).fake()->unique()->numerify('#######'),
            'email' => null,
            'password' => static::$password ??= Hash::make('password'),
            'status' => User::STATUS_ACTIVE,
            'remember_token' => Str::random(10),
        ];
    }

    public function withEmail(?string $email = null): static
    {
        return $this->state(fn () => [
            'email' => $email ?? fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => User::STATUS_SUSPENDED]);
    }

    /** An account the admin already accepted as an instructor. */
    public function instructor(): static
    {
        return $this->afterCreating(function (User $user) {
            InstructorFactory::new()->for($user)->create(['display_name' => $user->name]);
        });
    }
}
