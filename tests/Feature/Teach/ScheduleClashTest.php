<?php

namespace Tests\Feature\Teach;

use App\Models\TeachingClass;
use App\Models\User;
use App\Support\ScheduleClash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleClashTest extends TestCase
{
    use RefreshDatabase;

    /** 1 Dec 2026 is a Tuesday (2); Saturday = 6, Monday = 1. */
    private User $user;

    private TeachingClass $active;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->instructor()->create();
        $this->user->instructor->forceFill(['slug' => 'ahmed'])->save();

        // An active class: Saturdays and Mondays, 17:00 to 19:00, December and January.
        $this->active = TeachingClass::factory()->published()->for($this->user->instructor)->create([
            'title' => 'الفصل القائم',
            'starts_on' => '2026-12-01',
            'ends_on' => '2027-01-31',
            'schedule_type' => TeachingClass::SCHEDULE_WEEKLY,
            'days' => [6, 1],
            'start_time' => '17:00',
            'end_time' => '19:00',
        ]);

        $this->actingAs($this->user);
    }

    public function test_a_new_class_at_the_same_hours_on_a_shared_day_is_refused(): void
    {
        $this->post('/teach/classes', $this->data(['days' => [1, 3], 'start_time' => '18:00', 'end_time' => '20:00']))
            ->assertSessionHasErrors(['start_time' => ScheduleClash::message([
                'class' => $this->active,
                'date' => '2026-12-07', // the first Monday of the overlap
            ])]);
        $this->assertSame(1, TeachingClass::query()->count());

        // Daily meets on the active class's days too.
        $this->post('/teach/classes', $this->data(['schedule_type' => 'daily', 'days' => []]))
            ->assertSessionHasErrors('start_time');
    }

    public function test_classes_that_never_meet_together_are_fine(): void
    {
        // Other days.
        $this->post('/teach/classes', $this->data(['days' => [0, 2]]))->assertSessionHasNoErrors();
        // Same days, back to back (ends when the other starts).
        $this->post('/teach/classes', $this->data(['start_time' => '15:00', 'end_time' => '17:00']))->assertSessionHasNoErrors();
        $this->post('/teach/classes', $this->data(['start_time' => '19:00', 'end_time' => '21:00']))->assertSessionHasNoErrors();
        // Same days and hours, but after the active class has ended.
        $this->post('/teach/classes', $this->data(['starts_on' => '2027-02-01', 'ends_on' => '2027-03-31']))->assertSessionHasNoErrors();
        // A short period with no Saturday or Monday in it (Tue 1 Dec to Fri 4 Dec).
        $this->post('/teach/classes', $this->data(['schedule_type' => 'daily', 'days' => [], 'ends_on' => '2026-12-04']))
            ->assertSessionHasNoErrors();
    }

    public function test_hand_picked_dates_clash_only_on_a_shared_day(): void
    {
        // 9 Dec is a Wednesday: free. 12 Dec is a Saturday: taken.
        $this->post('/teach/classes', $this->data(['schedule_type' => 'manual', 'days' => [], 'manual_dates' => ['2026-12-09']]))
            ->assertSessionHasNoErrors();
        $this->post('/teach/classes', $this->data(['schedule_type' => 'manual', 'days' => [], 'manual_dates' => ['2026-12-09', '2026-12-12']]))
            ->assertSessionHasErrors('start_time');
    }

    public function test_drafts_and_canceled_classes_are_not_checked_against_and_a_class_not_against_itself(): void
    {
        $this->active->forceFill(['status' => TeachingClass::STATUS_CANCELED])->save();
        $this->post('/teach/classes', $this->data())->assertSessionHasNoErrors();

        $this->active->forceFill(['status' => TeachingClass::STATUS_PUBLISHED])->save();
        $draft = TeachingClass::query()->latest('id')->first();
        $this->assertSame(TeachingClass::STATUS_DRAFT, $draft->status);

        // The active class can be saved with its own hours (the draft does not count).
        $this->put("/teach/classes/{$this->active->id}", $this->data(['title' => 'الفصل القائم']))->assertSessionHasNoErrors();

        // The draft overlaps the active class: publishing it is refused.
        $draft->forceFill(['image_path' => 'class-images/1/test.jpg'])->save();
        $this->post("/teach/classes/{$draft->id}/publish")
            ->assertSessionHasErrors(['class' => ScheduleClash::message(['class' => $this->active->fresh(), 'date' => '2026-12-05'])]);
        $this->assertSame(TeachingClass::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_another_instructors_classes_do_not_count(): void
    {
        $other = User::factory()->instructor()->create();
        $this->active->forceFill(['instructor_id' => $other->instructor->id])->save();

        $this->post('/teach/classes', $this->data())->assertSessionHasNoErrors();
    }

    public function test_restarting_a_suspended_class_onto_another_class_is_refused(): void
    {
        $suspended = TeachingClass::factory()->for($this->user->instructor)->create([
            'status' => TeachingClass::STATUS_SUSPENDED,
            'starts_on' => '2026-11-03',
            'ends_on' => '2026-11-30',
            'schedule_type' => TeachingClass::SCHEDULE_WEEKLY,
            'days' => [6],
            'start_time' => '18:00',
            'end_time' => '20:00',
        ]);

        // Moved to start on 5 Dec, its Saturdays meet the active class's Saturdays.
        $this->post("/teach/classes/{$suspended->id}/reactivate", ['starts_on' => '2026-12-05'])
            ->assertSessionHasErrors('starts_on');
        $this->assertSame(TeachingClass::STATUS_SUSPENDED, $suspended->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(array $overrides = []): array
    {
        return [
            'title' => 'فصل جديد',
            'description' => 'نبذة.',
            'learn' => ['نقطة'],
            'requirements' => ['نقطة'],
            'audience' => ['نقطة'],
            'type' => 'online',
            'meeting_url' => '',
            'starts_on' => '2026-12-01',
            'ends_on' => '2027-01-31',
            'schedule_type' => 'weekly',
            'days' => [6, 1],
            'start_time' => '17:00',
            'end_time' => '19:00',
            'price' => '0',
            'seats' => '0',
            ...$overrides,
        ];
    }
}
