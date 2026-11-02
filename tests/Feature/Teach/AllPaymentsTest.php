<?php

namespace Tests\Feature\Teach;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AllPaymentsTest extends TestCase
{
    private User $owner;

    private TeachingClass $math;

    private TeachingClass $physics;

    protected function setUp(): void
    {
        parent::setUp();

        // 23:30 UTC on 9 Nov is already 10 Nov in Tripoli.
        Carbon::setTestNow(Carbon::parse('2026-11-09 23:30:00', 'UTC'));

        $this->owner = User::factory()->instructor()->create();
        $this->owner->instructor->forceFill(['slug' => 'teacher'])->save();
        $this->math = TeachingClass::factory()->published()->for($this->owner->instructor)->create(['title' => 'رياضيات', 'price' => '300']);
        $this->physics = TeachingClass::factory()->published()->for($this->owner->instructor)->create(['title' => 'فيزياء', 'price' => '200']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_all_payments_across_courses_with_totals(): void
    {
        $sara = $this->accept(User::factory()->create(['name' => 'سارة', 'phone' => '+218911234567']), $this->math, '300.000');
        $omar = $this->accept(User::factory()->create(['name' => 'عمر']), $this->physics, '200.000');

        $this->actingAs($this->owner);
        $this->pay($sara, ['amount' => '100', 'paid_at' => '2026-11-02', 'method' => 'cash']);
        $this->pay($sara, ['amount' => '50.5', 'paid_at' => '2026-10-15', 'method' => 'transfer']);
        $this->pay($omar, ['amount' => '80', 'paid_at' => '2026-11-10', 'method' => 'cash']);

        $this->get('/teach/payments')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Teach/AllPayments')
            ->has('payments.data', 3)
            // Newest first.
            ->where('payments.data.0.student.name', 'عمر')
            ->where('payments.data.0.class.title', 'فيزياء')
            ->where('payments.data.2.amount', '50.5')
            ->where('totals.collected', '230.5')
            ->where('totals.count', 3)
            ->where('totals.voided', 0)
            ->has('totals.by_method', 2)
            ->has('payable', 2)
            ->where('today', '2026-11-10')
            ->where('monthStart', '2026-11-01'));
    }

    public function test_filters_by_period_course_method_and_search(): void
    {
        $sara = $this->accept(User::factory()->create(['name' => 'سارة', 'phone' => '+218911234567']), $this->math, '300.000');
        $omar = $this->accept(User::factory()->create(['name' => 'عمر', 'phone' => '+218927654321']), $this->physics, '200.000');

        $this->actingAs($this->owner);
        $this->pay($sara, ['amount' => '100', 'paid_at' => '2026-11-02', 'method' => 'cash']);
        $this->pay($sara, ['amount' => '50', 'paid_at' => '2026-10-15', 'method' => 'transfer']);
        $this->pay($omar, ['amount' => '80', 'paid_at' => '2026-11-10', 'method' => 'cash']);

        // The period is days in Libya: 10 Nov includes the payment typed for that day.
        $this->get('/teach/payments?from=2026-11-01&to=2026-11-10')->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 2)->where('totals.collected', '180'));
        $this->get('/teach/payments?from=2026-10-01&to=2026-10-31')->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)->where('totals.collected', '50'));
        $this->get("/teach/payments?course={$this->physics->id}")->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)->where('payments.data.0.student.name', 'عمر'));
        $this->get('/teach/payments?method=transfer')->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)->where('totals.collected', '50'));
        $this->get('/teach/payments?q=سارة')->assertInertia(fn (Assert $page) => $page->has('payments.data', 2));
        $this->get('/teach/payments?q=0927654321')->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)->where('payments.data.0.student.name', 'عمر'));
        $this->get('/teach/payments?q=TL-20261110')->assertInertia(fn (Assert $page) => $page->has('payments.data', 3));

        $this->get('/teach/payments?from=2026-11-10&to=2026-11-01')->assertSessionHasErrors('to');
        $this->get('/teach/payments?method=bitcoin')->assertSessionHasErrors('method');
    }

    public function test_voided_payments_are_left_out_of_the_totals_and_shown_on_their_own_tab(): void
    {
        $sara = $this->accept(User::factory()->create(), $this->math, '300.000');

        $this->actingAs($this->owner);
        $this->pay($sara, ['amount' => '100']);
        $this->pay($sara, ['amount' => '40']);
        $voided = $sara->payments()->latest('id')->first();
        $this->post("/teach/payments/{$voided->id}/void", ['reason' => 'خطأ'])->assertSessionHasNoErrors();

        $this->get('/teach/payments')->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)
            ->where('totals.collected', '100')
            ->where('totals.count', 1)
            ->where('totals.voided', 1));
        $this->get('/teach/payments?status=voided')->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)
            ->where('payments.data.0.voided', true)
            ->where('payments.data.0.void_reason', 'خطأ'));
        $this->get('/teach/payments?status=all')->assertInertia(fn (Assert $page) => $page->has('payments.data', 2));
    }

    public function test_another_instructors_payments_and_courses_stay_out(): void
    {
        $other = User::factory()->instructor()->create();
        $foreignClass = TeachingClass::factory()->published()->for($other->instructor)->create();
        $foreign = $this->accept(User::factory()->create(), $foreignClass, '300.000');
        $this->actingAs($other);
        $this->pay($foreign, ['amount' => '100']);

        $this->actingAs($this->owner)->get('/teach/payments')->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 0)
            ->where('totals.collected', '0')
            ->has('payable', 0));
        $this->get("/teach/payments?course={$foreignClass->id}")->assertSessionHasErrors('course');
    }

    public function test_payable_lists_only_students_who_still_owe(): void
    {
        $owes = $this->accept(User::factory()->create(['name' => 'مدين']), $this->math, '300.000');
        $paid = $this->accept(User::factory()->create(['name' => 'مسدّد']), $this->physics, '200.000');
        Enrollment::factory()->for($this->math, 'teachingClass')->create(); // a request, not a student yet

        $this->actingAs($this->owner);
        $this->pay($paid, ['amount' => '200']);
        $this->pay($owes, ['amount' => '100']);

        $this->get('/teach/payments')->assertInertia(fn (Assert $page) => $page
            ->has('payable', 1)
            ->where('payable.0.id', $owes->id)
            ->where('payable.0.name', 'مدين')
            ->where('payable.0.remaining', '200'));
    }

    public function test_only_instructors_can_open_it(): void
    {
        $this->get('/teach/payments')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/teach/payments')->assertForbidden();
    }

    private function accept(User $user, TeachingClass $class, string $fee = '300.000'): Enrollment
    {
        return Enrollment::factory()->accepted($fee)->for($class, 'teachingClass')->for($user)->create();
    }

    /**
     * @param  array<string, string>  $data
     */
    private function pay(Enrollment $enrollment, array $data): void
    {
        $this->post("/teach/enrollments/{$enrollment->id}/payments", [
            'amount' => '10',
            'paid_at' => '2026-11-10',
            'method' => 'cash',
            'notes' => '',
            ...$data,
        ])->assertSessionHasNoErrors();
    }
}
