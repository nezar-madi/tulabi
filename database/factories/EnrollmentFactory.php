<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => TeachingClass::factory()->published(),
            'user_id' => User::factory(),
            'status' => Enrollment::STATUS_REQUESTED,
            'currency_code' => 'LYD',
            'requested_at' => now(),
        ];
    }

    public function accepted(?string $fee = '300.000'): static
    {
        return $this->state(fn () => [
            'status' => Enrollment::STATUS_ACCEPTED,
            'fee' => $fee,
            'decided_at' => now(),
            'accepted_at' => now(),
        ]);
    }
}
