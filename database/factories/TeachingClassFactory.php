<?php

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Support\TulabiClock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeachingClass>
 */
class TeachingClassFactory extends Factory
{
    protected $model = TeachingClass::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = TulabiClock::now()->addDays(7);

        return [
            'instructor_id' => Instructor::factory(),
            'title' => 'رياضيات الثالث الثانوي',
            'description' => '<p>مراجعة شاملة للمنهج.</p>',
            'type' => TeachingClass::TYPE_IN_PERSON,
            'location' => 'طرابلس، حي الأندلس',
            'map_url' => 'https://maps.app.goo.gl/abc123',
            'meeting_url' => null,
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->addWeeks(8)->toDateString(),
            'schedule_type' => TeachingClass::SCHEDULE_WEEKLY,
            'days' => [6, 1, 3],
            'start_time' => '17:00',
            'end_time' => '19:00',
            'price' => '300.000',
            'currency_code' => 'LYD',
            'seats' => 20,
            'status' => TeachingClass::STATUS_DRAFT,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => TeachingClass::STATUS_PUBLISHED, 'published_at' => now()]);
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'type' => TeachingClass::TYPE_ONLINE,
            'location' => null,
            'map_url' => null,
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ]);
    }
}
