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
            'description' => 'برنامج شامل للمنهج.',
            'learn' => null,
            'requirements' => null,
            'audience' => null,
            // The poster is what lets a program show on the public page.
            'image_path' => 'program-images/1/test.jpg',
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
