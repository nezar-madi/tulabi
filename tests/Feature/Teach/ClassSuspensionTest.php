<?php

namespace Tests\Feature\Teach;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * «تعليق البدء تلقائيًا»: a class that reaches its start date with fewer accepted students than
 * its minimum is suspended, and restarts only with a new start date (today or later).
 */
class ClassSuspensionTest extends TestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        // 22:30 UTC on 9 Nov is 00:30 on 10 Nov in Tripoli.
        Carbon::setTestNow(Carbon::parse('2026-11-09 22:30:00', 'UTC'));

        $this->owner = User::factory()->instructor()->create();
        $this->owner->instructor->forceFill(['slug' => 'teacher'])->save();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_minimum_is_asked_for_only_when_the_switch_is_on(): void
    {
        $this->actingAs($this->owner);

        $this->post('/teach/classes', $this->data(['auto_suspend' => true, 'min_seats' => '']))->assertSessionHasErrors('min_seats');
        $this->post('/teach/classes', $this->data(['auto_suspend' => true, 'min_seats' => '0']))->assertSessionHasErrors('min_seats');
        $this->post('/teach/classes', $this->data(['auto_suspend' => true, 'min_seats' => '30', 'seats' => '20']))->assertSessionHasErrors('min_seats');
        $this->assertSame(0, TeachingClass::query()->count());

        $this->post('/teach/classes', $this->data(['auto_suspend' => true, 'min_seats' => '5', 'seats' => '20']))->assertSessionHasNoErrors();
        $on = TeachingClass::query()->latest('id')->first();
        $this->assertTrue($on->auto_suspend);
        $this->assertSame(5, $on->min_seats);

        // Off: the minimum is dropped whatever was sent.
        $this->post('/teach/classes', $this->data(['auto_suspend' => false, 'min_seats' => '5']))->assertSessionHasNoErrors();
        $off = TeachingClass::query()->latest('id')->first();
        $this->assertFalse($off->auto_suspend);
        $this->assertNull($off->min_seats);
    }

    public function test_a_class_below_its_minimum_is_suspended_on_its_start_date(): void
    {
        $short = $this->published(['title' => 'ناقص', 'starts_on' => '2026-11-10', 'min_seats' => 3]);
        $this->accept($short, 2);
        $full = $this->published(['title' => 'مكتمل', 'starts_on' => '2026-11-10', 'min_seats' => 3]);
        $this->accept($full, 3);
        $later = $this->published(['title' => 'لاحق', 'starts_on' => '2026-11-11', 'min_seats' => 3]);
        $off = $this->published(['title' => 'بلا تعليق', 'starts_on' => '2026-11-10', 'auto_suspend' => false, 'min_seats' => null]);
        $closed = $this->published(['title' => 'مغلق', 'starts_on' => '2026-11-09', 'min_seats' => 3, 'status' => TeachingClass::STATUS_CLOSED]);

        $this->artisan('classes:check-start')->expectsOutput('2 class(es) suspended.')->assertSuccessful();

        $this->assertSame(TeachingClass::STATUS_SUSPENDED, $short->fresh()->status);
        $this->assertNotNull($short->fresh()->suspended_at);
        $this->assertSame(TeachingClass::STATUS_SUSPENDED, $closed->fresh()->status);
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $full->fresh()->status);
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $later->fresh()->status);
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $off->fresh()->status);

        // Only accepted students count; requested ones do not.
        $this->assertSame(1, TeachingClass::query()->where('status', TeachingClass::STATUS_PUBLISHED)->where('title', 'مكتمل')->count());
    }

    public function test_requests_and_rejected_students_do_not_count_toward_the_minimum(): void
    {
        $class = $this->published(['starts_on' => '2026-11-10', 'min_seats' => 2]);
        $this->accept($class, 1);
        Enrollment::factory()->for($class, 'teachingClass')->create(); // only a request

        $this->artisan('classes:check-start')->assertSuccessful();

        $this->assertTrue($class->fresh()->isSuspended());
    }

    public function test_the_instructors_own_pages_run_the_check_so_nothing_waits_for_the_schedule(): void
    {
        $class = $this->published(['starts_on' => '2026-11-10', 'min_seats' => 3]);

        $this->actingAs($this->owner)->get('/teach/classes')->assertInertia(fn (Assert $page) => $page
            ->where('classes.data.0.status', 'suspended'));
        $this->assertTrue($class->fresh()->isSuspended());

        $other = $this->published(['starts_on' => '2026-11-10', 'min_seats' => 3, 'title' => 'آخر']);
        $this->get('/teach')->assertOk();
        $this->assertTrue($other->fresh()->isSuspended());
    }

    public function test_a_suspended_class_is_hidden_from_visitors_and_takes_no_requests(): void
    {
        $class = $this->published(['starts_on' => '2026-11-10', 'min_seats' => 3]);
        $this->get("/teacher/classes/{$class->id}")->assertOk();

        $this->artisan('classes:check-start');

        $this->get("/teacher/classes/{$class->id}")->assertNotFound();
        $this->get('/teacher')->assertOk()->assertDontSee('رياضيات الثالث الثانوي');

        $student = User::factory()->create();
        $this->actingAs($student)->post("/classes/{$class->id}/request")->assertSessionHasErrors();
        $this->assertSame(0, Enrollment::query()->count());

        // The owner still sees it, and so does an accepted student on their own page.
        $accepted = Enrollment::factory()->accepted()->for($class, 'teachingClass')->for($student)->create();
        $this->actingAs($student)->get("/account/classes/{$accepted->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('enrollment.class.status', 'suspended'));
    }

    public function test_restarting_needs_a_new_start_date_today_or_later_and_moves_the_period(): void
    {
        $class = $this->published([
            'starts_on' => '2026-11-10',
            'ends_on' => '2026-12-10',
            'schedule_type' => 'manual',
            'days' => null,
            'manual_dates' => ['2026-11-10', '2026-11-17', '2026-12-10'],
            'min_seats' => 3,
        ]);
        $this->artisan('classes:check-start');
        $this->assertTrue($class->fresh()->isSuspended());

        $this->actingAs($this->owner);
        $this->post("/teach/classes/{$class->id}/reactivate", ['starts_on' => '2026-11-09'])->assertSessionHasErrors('starts_on');
        $this->post("/teach/classes/{$class->id}/reactivate", ['starts_on' => ''])->assertSessionHasErrors('starts_on');
        $this->assertTrue($class->fresh()->isSuspended());

        // Today (10 Nov in Libya) is allowed; everything moves by the same number of days.
        $this->post("/teach/classes/{$class->id}/reactivate", ['starts_on' => '2026-11-20'])->assertSessionHasNoErrors();

        $class->refresh();
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $class->status);
        $this->assertNull($class->suspended_at);
        $this->assertSame('2026-11-20', $class->starts_on->toDateString());
        $this->assertSame('2026-12-20', $class->ends_on->toDateString());
        $this->assertSame(['2026-11-20', '2026-11-27', '2026-12-20'], $class->manual_dates);

        // Today itself is accepted too (the class would be checked again at once).
        $again = $this->published(['starts_on' => '2026-11-10', 'min_seats' => 3]);
        $this->artisan('classes:check-start');
        $this->post("/teach/classes/{$again->id}/reactivate", ['starts_on' => '2026-11-10'])->assertSessionHasNoErrors();
    }

    public function test_only_a_suspended_class_can_be_restarted_and_only_by_its_owner(): void
    {
        $running = $this->published(['starts_on' => '2026-11-20']);
        $this->actingAs($this->owner)->post("/teach/classes/{$running->id}/reactivate", ['starts_on' => '2026-11-25'])
            ->assertSessionHasErrors(['class' => __('classes.wrong_status')]);

        $suspended = $this->published(['starts_on' => '2026-11-10', 'min_seats' => 3]);
        $this->artisan('classes:check-start');

        $other = User::factory()->instructor()->create();
        $this->actingAs($other)->post("/teach/classes/{$suspended->id}/reactivate", ['starts_on' => '2026-11-25'])->assertForbidden();
        $this->assertTrue($suspended->fresh()->isSuspended());
    }

    public function test_a_suspended_class_can_still_be_edited_and_canceled(): void
    {
        $class = $this->published(['starts_on' => '2026-11-10', 'min_seats' => 3]);
        $this->artisan('classes:check-start');
        $this->actingAs($this->owner);

        $this->put("/teach/classes/{$class->id}", $this->data(['title' => 'عنوان جديد']))->assertSessionHasNoErrors();
        $this->assertSame('عنوان جديد', $class->fresh()->title);
        $this->assertTrue($class->fresh()->isSuspended());

        $this->post("/teach/classes/{$class->id}/cancel", ['reason' => 'لا طلاب'])->assertSessionHasNoErrors();
        $this->assertTrue($class->fresh()->isCanceled());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function published(array $overrides = []): TeachingClass
    {
        return TeachingClass::factory()->published()->for($this->owner->instructor)->create([
            'auto_suspend' => true,
            'min_seats' => 3,
            'starts_on' => '2026-11-10',
            'ends_on' => '2026-12-31',
            ...$overrides,
        ]);
    }

    private function accept(TeachingClass $class, int $count): void
    {
        Enrollment::factory()->accepted()->count($count)->for($class, 'teachingClass')->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(array $overrides = []): array
    {
        return [
            'title' => 'رياضيات',
            'description' => 'شرح.',
            'type' => 'online',
            'meeting_url' => '',
            'schedule_type' => 'daily',
            'starts_on' => '2026-12-01',
            'price' => '100',
            ...$overrides,
        ];
    }
}
