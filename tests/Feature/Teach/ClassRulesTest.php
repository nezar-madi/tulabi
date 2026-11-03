<?php

namespace Tests\Feature\Teach;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\ClassLifecycle;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The rules found in the classes review (2026-11-03): auto-suspend checks once, a restart is final,
 * no past dates, a weekly class starts on one of its days, the price is fixed once a student joins,
 * no accepting after the end, students hear of schedule changes, a suspended class frees its hours,
 * an ended class's page is gone for visitors, and a deleted draft takes its image with it.
 */
class ClassRulesTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 15 Dec 2026 (a Tuesday) in Libya.
        $this->travelTo('2026-12-15 10:00:00');
        $this->user = User::factory()->instructor()->create();
        $this->user->instructor->forceFill(['slug' => 'ahmed'])->save();
    }

    public function test_auto_suspend_checks_once_at_the_start_and_never_a_running_or_ended_class(): void
    {
        // Started with enough students: checked, stays published, and a student leaving later changes nothing.
        $running = $this->published(['starts_on' => '2026-12-15', 'ends_on' => '2027-01-31', 'auto_suspend' => true, 'min_seats' => 2]);
        $first = $this->accept($running);
        $this->accept($running);
        app(ClassLifecycle::class)->suspendUnderfilled();
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $running->fresh()->status);
        $this->assertNotNull($running->fresh()->start_checked_at);

        $first->forceFill(['status' => Enrollment::STATUS_WITHDRAWN])->save();
        $this->travel(2)->days();
        app(ClassLifecycle::class)->suspendUnderfilled();
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $running->fresh()->status);

        // An ended class is never suspended.
        $ended = $this->published(['starts_on' => '2026-10-03', 'ends_on' => '2026-11-28', 'auto_suspend' => true, 'min_seats' => 3]);
        app(ClassLifecycle::class)->suspendUnderfilled();
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $ended->fresh()->status);
    }

    public function test_switching_auto_suspend_on_for_a_class_that_already_started_does_not_suspend_it(): void
    {
        $class = $this->published(['starts_on' => '2026-12-12', 'ends_on' => '2027-01-31', 'days' => [6, 1]]);
        $this->actingAs($this->user)
            ->put("/teach/classes/{$class->id}", $this->data($class, ['auto_suspend' => '1', 'min_seats' => '5']))
            ->assertSessionHasNoErrors();

        $this->get('/teach/classes');
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $class->fresh()->status);
    }

    public function test_moving_the_start_to_a_later_date_checks_it_again_on_the_new_date(): void
    {
        $class = $this->published(['starts_on' => '2026-12-19', 'ends_on' => '2027-01-31', 'days' => [6], 'auto_suspend' => true, 'min_seats' => 2]);
        $this->actingAs($this->user)
            ->put("/teach/classes/{$class->id}", $this->data($class, ['starts_on' => '2026-12-26', 'days' => [6], 'auto_suspend' => '1', 'min_seats' => '2']))
            ->assertSessionHasNoErrors();
        $this->assertNull($class->fresh()->start_checked_at);

        $this->travelTo('2026-12-26 10:00:00');
        app(ClassLifecycle::class)->suspendUnderfilled();
        $this->assertSame(TeachingClass::STATUS_SUSPENDED, $class->fresh()->status);
    }

    public function test_no_past_dates_but_a_running_class_keeps_its_own(): void
    {
        $this->actingAs($this->user);

        $this->post('/teach/classes', $this->newData(['starts_on' => '2026-12-14', 'schedule_type' => 'daily', 'days' => []]))
            ->assertSessionHasErrors(['starts_on' => __('classes.start_past')]);
        $this->assertSame(0, TeachingClass::query()->count());

        // A class running since 12 Dec saves with its start unchanged, but the start cannot move into the past.
        $running = $this->published(['starts_on' => '2026-12-12', 'ends_on' => '2027-01-31', 'days' => [6, 1]]);
        $this->put("/teach/classes/{$running->id}", $this->data($running, ['title' => 'عنوان جديد']))->assertSessionHasNoErrors();
        $this->put("/teach/classes/{$running->id}", $this->data($running, ['starts_on' => '2026-12-05']))
            ->assertSessionHasErrors(['starts_on' => __('classes.start_past')]);
        $this->put("/teach/classes/{$running->id}", $this->data($running, ['ends_on' => '2026-12-14']))
            ->assertSessionHasErrors(['ends_on' => __('classes.end_past')]);
    }

    public function test_an_ended_class_cannot_be_published_and_its_page_is_gone_for_visitors(): void
    {
        $draft = TeachingClass::factory()->for($this->user->instructor)->create(['starts_on' => '2026-10-03', 'ends_on' => '2026-11-28']);
        $this->actingAs($this->user)->post("/teach/classes/{$draft->id}/publish")
            ->assertSessionHasErrors(['class' => __('classes.publish_ended')]);
        $this->assertSame(TeachingClass::STATUS_DRAFT, $draft->fresh()->status);

        $ended = $this->published(['starts_on' => '2026-10-03', 'ends_on' => '2026-11-28']);
        $this->get("/ahmed/classes/{$ended->id}")->assertOk(); // the owner
        auth('web')->logout();
        $this->get("/ahmed/classes/{$ended->id}")->assertNotFound();
    }

    public function test_a_weekly_class_starts_on_one_of_its_days(): void
    {
        $this->actingAs($this->user);

        // 19 Dec is a Saturday; 21 Dec a Monday.
        $this->post('/teach/classes', $this->newData(['starts_on' => '2026-12-21', 'days' => [6]]))
            ->assertSessionHasErrors('starts_on');
        $this->post('/teach/classes', $this->newData(['starts_on' => '2026-12-19', 'days' => [6]]))->assertSessionHasNoErrors();

        // An old class whose start is not one of its days still saves while its start and days stay.
        $old = $this->published(['starts_on' => '2026-12-16', 'ends_on' => '2027-01-31', 'days' => [6], 'start_time' => '08:00', 'end_time' => '09:00']);
        $this->put("/teach/classes/{$old->id}", $this->data($old, ['title' => 'جديد']))->assertSessionHasNoErrors();
    }

    public function test_the_price_is_fixed_once_a_student_has_joined(): void
    {
        $class = $this->published(['starts_on' => '2026-12-19', 'ends_on' => '2027-01-31', 'days' => [6], 'price' => '100']);
        $this->actingAs($this->user);

        // Nobody yet: the price may change.
        $this->put("/teach/classes/{$class->id}", $this->data($class, ['price' => '120']))->assertSessionHasNoErrors();

        // A request waiting: fixed (the same price still saves).
        $request = Enrollment::factory()->for($class->fresh(), 'teachingClass')->create();
        $this->put("/teach/classes/{$class->id}", $this->data($class->fresh(), ['price' => '500']))
            ->assertSessionHasErrors(['price' => __('classes.price_locked')]);
        $this->put("/teach/classes/{$class->id}", $this->data($class->fresh(), ['price' => '120.000']))->assertSessionHasNoErrors();
        $this->get("/teach/classes/{$class->id}/edit")->assertInertia(fn (Assert $page) => $page->where('item.price_locked', true));

        // Even after the request is withdrawn, a student accepted before keeps it fixed.
        $this->post("/teach/enrollments/{$request->id}/accept");
        $request->fresh()->forceFill(['status' => Enrollment::STATUS_WITHDRAWN])->save();
        $this->put("/teach/classes/{$class->id}", $this->data($class->fresh(), ['price' => '500']))->assertSessionHasErrors('price');
        $this->assertSame('120.000', (string) $request->fresh()->fee);
    }

    public function test_no_student_is_accepted_after_the_class_ended(): void
    {
        $class = $this->published(['starts_on' => '2026-12-19', 'ends_on' => '2026-12-31', 'days' => [6]]);
        $request = Enrollment::factory()->for($class, 'teachingClass')->create();

        $this->travelTo('2027-01-05 10:00:00');
        $this->actingAs($this->user)->post("/teach/enrollments/{$request->id}/accept")
            ->assertSessionHasErrors(['enrollment' => __('enrollments.class_ended')]);
        $this->assertSame(Enrollment::STATUS_REQUESTED, $request->fresh()->status);
        // It can still be rejected.
        $this->post("/teach/enrollments/{$request->id}/reject")->assertSessionHasNoErrors();
    }

    public function test_accepted_students_are_told_when_the_dates_hours_or_place_change(): void
    {
        $class = $this->published(['starts_on' => '2026-12-19', 'ends_on' => '2027-01-31', 'days' => [6]]);
        $this->actingAs($this->user);

        // No students yet: nothing to tell.
        $this->put("/teach/classes/{$class->id}", $this->data($class, ['start_time' => '18:00', 'end_time' => '20:00']))
            ->assertSessionHas('scheduleChanged', false);
        $this->assertNull($class->fresh()->schedule_changed_at);

        $student = $this->accept($class);

        // The title is not the schedule.
        $this->put("/teach/classes/{$class->id}", $this->data($class->fresh(), ['title' => 'عنوان آخر']))
            ->assertSessionHas('scheduleChanged', false);

        $this->put("/teach/classes/{$class->id}", $this->data($class->fresh(), ['start_time' => '16:00', 'end_time' => '18:00']))
            ->assertSessionHas('scheduleChanged', true);
        $this->get("/teach/classes/{$class->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->where('scheduleShareUrl', fn (string $url) => str_starts_with($url, 'https://wa.me/?text=')
                && str_contains(rawurldecode($url), '4:00 م - 6:00 م')));

        $this->actingAs($student->user)->get("/account/classes/{$student->id}")
            ->assertInertia(fn (Assert $page) => $page->where('enrollment.class.schedule_changed_at', fn ($value) => $value !== null));

        $this->travel(TeachingClass::MEETING_URL_NEW_DAYS + 1)->days();
        $this->get("/account/classes/{$student->id}")
            ->assertInertia(fn (Assert $page) => $page->where('enrollment.class.schedule_changed_at', null));
    }

    public function test_a_suspended_class_frees_its_hours_and_its_restart_is_checked(): void
    {
        $suspended = TeachingClass::factory()->for($this->user->instructor)->create([
            'status' => TeachingClass::STATUS_SUSPENDED, 'starts_on' => '2026-12-12', 'ends_on' => '2027-01-31',
            'schedule_type' => 'daily', 'days' => null, 'start_time' => '10:00', 'end_time' => '11:00',
        ]);

        $this->actingAs($this->user)->post('/teach/classes', $this->newData([
            'starts_on' => '2026-12-19', 'days' => [6], 'start_time' => '10:00', 'end_time' => '11:00',
        ]))->assertSessionHasNoErrors();
        $new = TeachingClass::query()->latest('id')->first();
        $new->forceFill(['image_path' => 'class-images/1/new.jpg'])->save();
        $this->post("/teach/classes/{$new->id}/publish")->assertSessionHasNoErrors();
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $new->fresh()->status);

        // Restarting it now lands on the new class's Saturdays.
        $this->post("/teach/classes/{$suspended->id}/reactivate", ['starts_on' => '2026-12-19'])->assertSessionHasErrors('starts_on');
        $this->assertTrue($suspended->fresh()->isSuspended());
    }

    public function test_an_ended_class_cannot_be_canceled_so_what_students_owe_stays(): void
    {
        $ended = $this->published(['starts_on' => '2026-10-03', 'ends_on' => '2026-11-28']);
        $enrollment = Enrollment::factory()->accepted()->for($ended, 'teachingClass')->create(['fee' => '300']);

        $this->actingAs($this->user)->post("/teach/classes/{$ended->id}/cancel", ['reason' => 'x'])
            ->assertSessionHasErrors(['class' => __('classes.cancel_ended')]);
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $ended->fresh()->status);
        $this->assertSame(300000, $enrollment->fresh()->remainingMillis());

        // A class still running can be canceled.
        $running = $this->published(['starts_on' => '2026-12-12', 'ends_on' => '2027-01-31', 'start_time' => '08:00', 'end_time' => '09:00']);
        $this->post("/teach/classes/{$running->id}/cancel")->assertSessionHasNoErrors();
        $this->assertTrue($running->fresh()->isCanceled());
    }

    public function test_the_hourly_check_reaches_every_class_in_one_run(): void
    {
        // More than one batch (1000) of classes due the same day.
        TeachingClass::factory()->published()->for($this->user->instructor)->count(1005)->create([
            'starts_on' => '2026-12-15', 'ends_on' => '2027-01-31', 'auto_suspend' => true, 'min_seats' => 1,
        ]);

        $this->assertSame(1005, app(ClassLifecycle::class)->suspendUnderfilled());
        $this->assertSame(0, TeachingClass::query()->whereNull('start_checked_at')->count());
    }

    public function test_deleting_a_draft_deletes_its_image_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('class-images/1/draft.jpg', 'x');
        $draft = TeachingClass::factory()->for($this->user->instructor)->create(['image_path' => 'class-images/1/draft.jpg']);

        $this->actingAs($this->user)->delete("/teach/classes/{$draft->id}")->assertRedirect();
        Storage::disk('public')->assertMissing('class-images/1/draft.jpg');
    }

    private function published(array $attributes): TeachingClass
    {
        return TeachingClass::factory()->published()->for($this->user->instructor)->create([
            'schedule_type' => 'weekly', 'start_time' => '17:00', 'end_time' => '19:00', ...$attributes,
        ]);
    }

    private function accept(TeachingClass $class): Enrollment
    {
        return Enrollment::factory()->accepted()->for($class, 'teachingClass')->create();
    }

    /** What the form sends for an existing class, with changes. */
    private function data(TeachingClass $class, array $overrides = []): array
    {
        return [
            'title' => $class->title,
            'description' => 'نبذة.',
            'learn' => ['أ'], 'requirements' => ['ب'], 'audience' => ['ج'],
            'type' => $class->type,
            'location' => $class->location ?? '',
            'map_url' => $class->map_url ?? '',
            'meeting_url' => '',
            'starts_on' => $class->starts_on->toDateString(),
            'ends_on' => $class->ends_on?->toDateString(),
            'schedule_type' => $class->schedule_type,
            'days' => $class->days ?? [],
            'start_time' => substr((string) $class->start_time, 0, 5),
            'end_time' => substr((string) $class->end_time, 0, 5),
            'price' => (string) $class->price,
            'seats' => (string) ($class->seats ?? 0),
            ...$overrides,
        ];
    }

    private function newData(array $overrides = []): array
    {
        return [
            'title' => 'فصل جديد', 'description' => 'نبذة.',
            'learn' => ['أ'], 'requirements' => ['ب'], 'audience' => ['ج'],
            'type' => 'online', 'meeting_url' => '',
            'starts_on' => '2026-12-19', 'ends_on' => '2027-01-31',
            'schedule_type' => 'weekly', 'days' => [6],
            'start_time' => '12:00', 'end_time' => '13:00', 'price' => '0', 'seats' => '0',
            ...$overrides,
        ];
    }
}
