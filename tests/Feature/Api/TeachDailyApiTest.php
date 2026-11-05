<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Payment;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\Payments;
use Tests\TestCase;

class TeachDailyApiTest extends TestCase
{
    public function test_only_active_instructors_reach_the_instructor_side(): void
    {
        $this->getJson('/api/v1/teach/home')->assertUnauthorized();
        $this->getJson('/api/v1/teach/home', $this->bearer(User::factory()->create()))->assertForbidden();

        $instructor = $this->instructor();
        $this->getJson('/api/v1/teach/home', $this->bearer($instructor->user))
            ->assertOk()
            ->assertJsonStructure(['profile', 'counts', 'upcoming', 'students', 'money' => ['collected', 'remaining', 'currency'], 'pending_requests']);

        // An empty count is still an object.
        $this->assertStringContainsString('"counts":{}', $this->getJson('/api/v1/teach/home', $this->bearer($instructor->user))->getContent());
    }

    public function test_requests_are_accepted_or_rejected_once(): void
    {
        $instructor = $this->instructor();
        $class = $this->publishedClass($instructor);
        $first = Enrollment::factory()->for($class, 'teachingClass')->create();
        $second = Enrollment::factory()->for($class, 'teachingClass')->create();
        $auth = $this->bearer($instructor->user);

        $this->getJson('/api/v1/teach/requests', $auth)
            ->assertOk()
            ->assertJsonCount(2, 'requests')
            ->assertJsonPath('requests.0.id', $first->id)
            ->assertJsonPath('requests.0.accepted_count', 0);

        $this->postJson("/api/v1/teach/enrollments/{$first->id}/accept", [], $auth)
            ->assertOk()
            ->assertJsonPath('enrollment.status', Enrollment::STATUS_ACCEPTED)
            ->assertJsonPath('enrollment.totals.remaining', $first->fresh()->fee === null ? '0' : $this->money($first->fresh()->fee));
        $this->postJson("/api/v1/teach/enrollments/{$first->id}/accept", [], $auth)
            ->assertJsonValidationErrors(['enrollment' => __('enrollments.already_decided')]);

        $this->postJson("/api/v1/teach/enrollments/{$second->id}/reject", ['reason' => 'الفصل ممتلئ'], $auth)
            ->assertOk()
            ->assertJsonPath('enrollment.status', Enrollment::STATUS_REJECTED)
            ->assertJsonPath('enrollment.decision_reason', 'الفصل ممتلئ');

        $this->getJson('/api/v1/teach/requests', $auth)->assertJsonCount(0, 'requests');

        $this->postJson("/api/v1/teach/enrollments/{$first->id}/remove", ['reason' => 'انقطع'], $auth)
            ->assertOk()
            ->assertJsonPath('enrollment.status', Enrollment::STATUS_CANCELED);
    }

    public function test_another_instructors_students_and_payments_are_out_of_reach(): void
    {
        $owner = $this->instructor();
        $class = $this->publishedClass($owner);
        $enrollment = Enrollment::factory()->accepted('300.000')->for($class, 'teachingClass')->create();
        $payment = app(Payments::class)->record($enrollment, ['amount' => '100', 'paid_at' => now()->toDateString(), 'method' => 'cash'], $owner->user);
        $other = $this->bearer($this->instructor()->user);

        $this->getJson("/api/v1/teach/classes/{$class->id}/students", $other)->assertNotFound();
        $this->getJson("/api/v1/teach/enrollments/{$enrollment->id}/payments", $other)->assertForbidden();
        $this->postJson("/api/v1/teach/enrollments/{$enrollment->id}/remove", [], $other)->assertForbidden();
        $this->postJson("/api/v1/teach/enrollments/{$enrollment->id}/payments", $this->payment(), $other)->assertForbidden();
        $this->putJson("/api/v1/teach/payments/{$payment->id}", ['method' => 'transfer'], $other)->assertForbidden();
        $this->postJson("/api/v1/teach/payments/{$payment->id}/void", ['reason' => 'خطأ'], $other)->assertForbidden();
        $this->getJson('/api/v1/teach/payments', $other)->assertOk()->assertJsonCount(0, 'payments.data');

        $this->assertSame(Enrollment::STATUS_ACCEPTED, $enrollment->fresh()->status);
        $this->assertSame(1, Payment::query()->count());
        $this->assertNull($payment->fresh()->voided_at);
    }

    public function test_payments_are_recorded_edited_and_voided_never_deleted(): void
    {
        $instructor = $this->instructor();
        $class = $this->publishedClass($instructor);
        $enrollment = Enrollment::factory()->accepted('300.000')->for($class, 'teachingClass')->create();
        $auth = $this->bearer($instructor->user);

        $this->getJson("/api/v1/teach/enrollments/{$enrollment->id}/payments", $auth)
            ->assertOk()
            ->assertJsonPath('canPay', true)
            ->assertJsonPath('totals.remaining', '300')
            ->assertJsonPath('methods', Payment::MANUAL_METHODS);

        $id = $this->postJson("/api/v1/teach/enrollments/{$enrollment->id}/payments", $this->payment(['amount' => '120']), $auth)
            ->assertCreated()
            ->assertJsonPath('payment.amount', '120')
            ->assertJsonPath('totals.remaining', '180')
            ->json('payment.id');

        $this->assertStringContainsString('/api/v1/receipts/'.$id, $this->getJson("/api/v1/teach/enrollments/{$enrollment->id}/payments", $auth)->json('payments.0.receipt_url'));

        // More than what is left, or a future date: refused.
        $this->postJson("/api/v1/teach/enrollments/{$enrollment->id}/payments", $this->payment(['amount' => '500']), $auth)
            ->assertJsonValidationErrors('amount');
        $this->postJson("/api/v1/teach/enrollments/{$enrollment->id}/payments", $this->payment(['paid_at' => now()->addDays(3)->toDateString()]), $auth)
            ->assertJsonValidationErrors('paid_at');

        // Method and notes change; the amount does not.
        $this->putJson("/api/v1/teach/payments/{$id}", ['method' => 'transfer', 'notes' => 'حوالة', 'amount' => '999'], $auth)
            ->assertOk()
            ->assertJsonPath('payment.method', 'transfer')
            ->assertJsonPath('payment.notes', 'حوالة')
            ->assertJsonPath('payment.amount', '120');

        $this->postJson("/api/v1/teach/payments/{$id}/void", [], $auth)->assertJsonValidationErrors('reason');
        $this->postJson("/api/v1/teach/payments/{$id}/void", ['reason' => 'سُجّلت بالخطأ'], $auth)
            ->assertOk()
            ->assertJsonPath('payment.voided', true)
            ->assertJsonPath('totals.remaining', '300');
        $this->postJson("/api/v1/teach/payments/{$id}/void", ['reason' => 'مرة أخرى'], $auth)
            ->assertJsonValidationErrors(['payment' => __('payments.already_voided')]);

        $this->assertSame(1, Payment::query()->count());
    }

    public function test_the_lists_filter_and_page(): void
    {
        $instructor = $this->instructor();
        $class = $this->publishedClass($instructor);
        $paid = Enrollment::factory()->accepted('300.000')->for($class, 'teachingClass')->create();
        Enrollment::factory()->accepted('300.000')->for($class, 'teachingClass')->create();
        app(Payments::class)->record($paid, ['amount' => '50', 'paid_at' => now()->toDateString(), 'method' => 'cash'], $instructor->user);
        $auth = $this->bearer($instructor->user);

        $this->getJson('/api/v1/teach/students', $auth)
            ->assertOk()
            ->assertJsonPath('counts.active', 2)
            ->assertJsonPath('students.total', 2)
            ->assertJsonPath('totals.remaining', '550');

        $this->getJson('/api/v1/teach/payments?method=transfer', $auth)->assertOk()->assertJsonPath('payments.total', 0);
        $this->getJson('/api/v1/teach/payments?method=cash', $auth)
            ->assertOk()
            ->assertJsonPath('payments.total', 1)
            ->assertJsonPath('totals.collected', '50');
        $this->getJson('/api/v1/teach/payments?status=nonsense', $auth)->assertJsonValidationErrors('status');
    }

    public function test_a_password_link_is_for_an_accepted_student(): void
    {
        $instructor = $this->instructor();
        $class = $this->publishedClass($instructor);
        $accepted = Enrollment::factory()->accepted()->for($class, 'teachingClass')->create();
        $waiting = Enrollment::factory()->for($class, 'teachingClass')->create();
        $auth = $this->bearer($instructor->user);

        $this->postJson("/api/v1/teach/enrollments/{$accepted->id}/reset-link", [], $auth)
            ->assertOk()
            ->assertJsonStructure(['reset_link' => ['name', 'url', 'whatsapp']]);

        $this->postJson("/api/v1/teach/enrollments/{$waiting->id}/reset-link", [], $auth)
            ->assertJsonValidationErrors(['enrollment' => __('enrollments.reset_only_students')]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payment(array $overrides = []): array
    {
        return ['amount' => '100', 'paid_at' => now()->toDateString(), 'method' => 'cash', ...$overrides];
    }

    private function money(string $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    }

    /** @return array<string, string> */
    private function bearer(User $user): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    private function instructor(): Instructor
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => 'teacher-'.$user->id])->save();

        return $user->instructor;
    }

    private function publishedClass(Instructor $instructor): TeachingClass
    {
        return TeachingClass::factory()->published()->for($instructor)->create(['price' => '300']);
    }
}
