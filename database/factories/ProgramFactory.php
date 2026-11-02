<?php

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'instructor_id' => Instructor::factory(),
            'name' => 'رياضيات',
            'description' => '<p>برنامج شامل للمنهج.</p>',
            'is_active' => true,
            'is_published' => false,
            'is_featured' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true]);
    }
}
