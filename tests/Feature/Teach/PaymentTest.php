<?php

namespace Tests\Feature\Teach;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\Payments;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 23:30 UTC on 9 Nov is already 10 Nov in Tripoli.
        Carbon::setTestNow(Carbon::parse('2026-11-09 23:30:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_record_a_payment_with_a_receipt_number_and_totals(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('300');

        $this->actingAs($owner)->get("/teach/enrollments/{$enrollment->id}/payments")->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teach/Payments')
                ->where('totals', ['fee' => '300', 'paid' => '0', 'remaining' => '300'])
                ->where('canPay', true)
                ->where('today', '2026-11-10'));

        $this->post("/teach/enrollments/{$enrollment->id}/payments", [
            'amount' => '120.5',
            'paid_at' => '2026-11-10',
            'method' => 'cash',
            'notes' => 'الدفعة الأولى',
            'receipt_number' => 'HACK',
            'instructor_id' => 999,
        ])->assertSessionHas('status', __('payments.recorded', ['number' => 'TL-20261110-0001']));

        $payment = Payment::query()->sole();
        $this->assertSame('120.500', $payment->amount);
        $this->assertSame('TL-20261110-0001', $payment->receipt_number);
        $this->assertSame($owner->instructor->id, $payment->instructor_id);
        $this->assertSame($owner->id, $payment->recorded_by);
        $this->assertSame('LYD', $payment->currency_code);
        // A typed date is local midnight in Tripoli, stored in UTC.
        $this->assertSame('2026-11-09 22:00:00', $payment->getRawOriginal('paid_at'));
        $this->assertSame('2026-11-10', $payment->paidOn());

        $this->get("/teach/enrollments/{$enrollment->id}/payments")
            ->assertInertia(fn (Assert $page) => $page
                ->where('totals.paid', '120.5')
                ->where('totals.remaining', '179.5')
                ->has('payments', 1));
    }

    public function test_never_more_than_the_remaining_and_never_a_future_date(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('100');
        $this->actingAs($owner);

        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '100.001']))
            ->assertSessionHasErrors(['amount' => __('payments.more_than_remaining', ['remaining' => '100'])]);
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['paid_at' => '2026-11-11']))
            ->assertSessionHasErrors(['paid_at' => __('payments.date_future')]);
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '0']))->assertSessionHasErrors('amount');
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '1.2345']))->assertSessionHasErrors('amount');
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['method' => 'dpay']))->assertSessionHasErrors('method');
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['paid_at' => '1999-12-31']))->assertSessionHasErrors('paid_at');

        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '60']))->assertSessionHasNoErrors();
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '40']))->assertSessionHasNoErrors();
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '1']))->assertSessionHasErrors('amount');

        $this->assertSame(2, Payment::query()->count());
    }

    public function test_no_payments_for_canceled_classes_or_students_who_left(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('100');
        $this->actingAs($owner);

        $enrollment->forceFill(['status' => Enrollment::STATUS_WITHDRAWN])->save();
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data())
            ->assertSessionHasErrors(['amount' => __('payments.not_member')]);

        $enrollment->forceFill(['status' => Enrollment::STATUS_ACCEPTED])->save();
        $enrollment->teachingClass->forceFill(['status' => TeachingClass::STATUS_CANCELED])->save();
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data())
            ->assertSessionHasErrors(['amount' => __('payments.class_canceled')]);

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_void_keeps_the_row_and_number_and_frees_the_amount(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('100');
        $this->actingAs($owner);
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '100']));
        $payment = Payment::query()->sole();

        $this->post("/teach/payments/{$payment->id}/void", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->post("/teach/payments/{$payment->id}/void", ['reason' => 'خطأ في المبلغ'])->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertNotNull($payment->voided_at);
        $this->assertSame('خطأ في المبلغ', $payment->void_reason);
        $this->assertSame($owner->id, $payment->voided_by);
        $this->assertSame(100000, $enrollment->fresh()->remainingMillis());

        $this->post("/teach/payments/{$payment->id}/void", ['reason' => 'مرة ثانية'])->assertSessionHasErrors('payment');

        // Recorded again: the next number, the voided one is never reused.
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '100']));
        $this->assertSame(
            ['TL-20261110-0001', 'TL-20261110-0002'],
            Payment::query()->orderBy('id')->pluck('receipt_number')->all(),
        );

        // Never deleted.
        $payment->delete();
        $this->assertNotNull(Payment::query()->find($payment->id));
    }

    public function test_only_method_and_notes_can_change(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('100');
        $this->actingAs($owner);
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '50']));
        $payment = Payment::query()->sole();

        $this->put("/teach/payments/{$payment->id}", [
            'method' => 'transfer',
            'notes' => 'تحويل مصرفي',
            'amount' => '1',
            'paid_at' => '2026-01-01',
        ])->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertSame('transfer', $payment->method);
        $this->assertSame('تحويل مصرفي', $payment->notes);
        $this->assertSame('50.000', $payment->amount);
        $this->assertSame('2026-11-10', $payment->paidOn());
    }

    public function test_another_instructor_cannot_see_or_touch_these_payments(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('100');
        $this->actingAs($owner)->post("/teach/enrollments/{$enrollment->id}/payments", $this->data(['amount' => '50']));
        $payment = Payment::query()->sole();

        [$other] = $this->acceptedStudent('100');
        $this->actingAs($other);

        $this->get("/teach/enrollments/{$enrollment->id}/payments")->assertForbidden();
        $this->post("/teach/enrollments/{$enrollment->id}/payments", $this->data())->assertForbidden();
        $this->put("/teach/payments/{$payment->id}", ['method' => 'other'])->assertForbidden();
        $this->post("/teach/payments/{$payment->id}/void", ['reason' => 'لا'])->assertForbidden();
        $this->get("/receipts/{$payment->id}")->assertForbidden();

        // Nor the student.
        $this->actingAs($enrollment->user);
        $this->post("/teach/payments/{$payment->id}/void", ['reason' => 'لا'])->assertForbidden();

        $payment->refresh();
        $this->assertNull($payment->voided_at);
        $this->assertSame('cash', $payment->method);
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_receipt_numbers_are_per_instructor_and_restart_each_day(): void
    {
        [$a, $first] = $this->acceptedStudent('1000');
        [$b, $second] = $this->acceptedStudent('1000');
        $payments = app(Payments::class);

        $payments->record($first, $this->data(), $a);
        $payments->record($first, $this->data(), $a);
        $payments->record($second, $this->data(), $b);

        Carbon::setTestNow(Carbon::parse('2026-11-10 23:00:00', 'UTC')); // 11 Nov in Tripoli
        $payments->record($first, $this->data(['paid_at' => '2026-11-11']), $a);

        $this->assertSame(
            ['TL-20261110-0001', 'TL-20261110-0002', 'TL-20261110-0001', 'TL-20261111-0001'],
            Payment::query()->orderBy('id')->pluck('receipt_number')->all(),
        );
        $this->assertSame(2, DB::table('receipt_counters')->count());
    }

    public function test_the_dashboard_shows_this_months_money(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('300');
        [, $other] = $this->acceptedStudent('500'); // another instructor
        $payments = app(Payments::class);

        $payments->record($enrollment, $this->data(['amount' => '100']), $owner);
        $payments->record($enrollment, $this->data(['amount' => '50', 'paid_at' => '2026-10-31']), $owner);
        $voided = $payments->record($enrollment, $this->data(['amount' => '25']), $owner);
        $payments->void($voided, 'خطأ', $owner);
        $payments->record($other, $this->data(['amount' => '200']), $other->teachingClass->instructor->user);

        $this->actingAs($owner)->get('/teach')->assertInertia(fn (Assert $page) => $page
            ->where('money.collected', '100')
            ->where('money.remaining', '150'));
    }

    /**
     * @return array{0: User, 1: Enrollment}
     */
    private function acceptedStudent(string $fee): array
    {
        $owner = User::factory()->instructor()->create();
        $owner->instructor->forceFill(['slug' => 'teacher-'.$owner->id])->save();
        $class = TeachingClass::factory()->published()->for($owner->instructor)->create(['price' => $fee]);
        $enrollment = Enrollment::factory()->for($class, 'teachingClass')->accepted($fee)->create();

        return [$owner, $enrollment];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(array $overrides = []): array
    {
        return [
            'amount' => '10',
            'paid_at' => '2026-11-10',
            'method' => 'cash',
            'notes' => '',
            ...$overrides,
        ];
    }
}
