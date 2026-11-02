<?php

namespace Tests\Feature\Student;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\Payments;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    public function test_the_student_sees_their_payments_totals_and_receipts(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('300');
        $payment = app(Payments::class)->record($enrollment, $this->data('100'), $owner);
        app(Payments::class)->record($this->acceptedStudent('50')[1], $this->data('50'), $owner); // someone else

        $this->actingAs($enrollment->user);

        $this->get("/account/classes/{$enrollment->id}")->assertInertia(fn (Assert $page) => $page
            ->where('totals', ['fee' => '300', 'paid' => '100', 'remaining' => '200'])
            ->has('payments', 1)
            ->where('payments.0.receipt_number', $payment->receipt_number));

        $this->get('/account/payments')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Student/Payments')
            ->has('payments', 1)
            ->where('payments.0.id', $payment->id));

        $response = $this->get("/receipts/{$payment->id}")->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_receipts_are_private(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('300');
        $payment = app(Payments::class)->record($enrollment, $this->data('100'), $owner);

        $this->get("/receipts/{$payment->id}")->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get("/receipts/{$payment->id}")->assertForbidden();
        $this->actingAs($owner)->get("/receipts/{$payment->id}")->assertOk();
    }

    public function test_a_voided_receipt_still_opens_and_a_canceled_class_owes_nothing(): void
    {
        [$owner, $enrollment] = $this->acceptedStudent('300');
        $payment = app(Payments::class)->record($enrollment, $this->data('100'), $owner);
        app(Payments::class)->void($payment, 'خطأ', $owner);

        $this->actingAs($enrollment->user)->get("/receipts/{$payment->id}")->assertOk();

        $enrollment->teachingClass->forceFill(['status' => TeachingClass::STATUS_CANCELED])->save();
        $this->get("/account/classes/{$enrollment->id}")->assertInertia(fn (Assert $page) => $page
            ->where('enrollment.status', Enrollment::STATUS_CANCELED)
            ->where('totals.remaining', '0')
            ->where('payments.0.voided', true));
    }

    /**
     * @return array{0: User, 1: Enrollment}
     */
    private function acceptedStudent(string $fee): array
    {
        $owner = User::factory()->instructor()->create();
        $owner->instructor->forceFill(['slug' => 'teacher-'.$owner->id])->save();
        $class = TeachingClass::factory()->published()->for($owner->instructor)->create(['price' => $fee]);

        return [$owner, Enrollment::factory()->for($class, 'teachingClass')->accepted($fee)->create()];
    }

    /**
     * @return array<string, mixed>
     */
    private function data(string $amount): array
    {
        return ['amount' => $amount, 'paid_at' => now('Africa/Tripoli')->toDateString(), 'method' => 'cash'];
    }
}
