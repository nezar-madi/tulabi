<?php

namespace Database\Factories;

use App\Models\InstructorApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorApplication>
 */
class InstructorApplicationFactory extends Factory
{
    protected $model = InstructorApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'specialty' => 'رياضيات',
            'city' => 'طرابلس',
            'bio' => 'مدرّس رياضيات للمرحلة الثانوية.',
            'status' => InstructorApplication::STATUS_PENDING,
        ];
    }
}
