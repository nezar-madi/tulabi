<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Support\TwoFactor\Totp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ];
    }

    /**
     * An account whose authenticator is already set up (no recovery codes).
     */
    public function withTwoFactor(?string $secret = null): static
    {
        return $this->afterMaking(function (Admin $admin) use ($secret) {
            $admin->forceFill([
                'two_factor_secret' => $secret ?? Totp::generateSecret(),
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => json_encode([]),
            ]);
        });
    }
}
