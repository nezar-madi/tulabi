<?php

namespace Tests\Feature\Teach;

use App\Models\Enrollment;
use App\Models\PasswordResetLink;
use App\Models\TeachingClass;
use App\Models\User;
use App\Notifications\EnrollmentDecided;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    public function test_the_instructor_sees_waiting_requests_and_accepts_with_the_class_price(): void
    {
        [$owner, $class] = $this->classWithOwner(['price' => '250.500']);
        $request = Enrollment::factory()->for($class, 'teachingClass')->create(['note' => 'سؤال']);

        $this->actingAs($owner)->get('/teach/requests')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Teach/Requests')
            ->has('requests', 1)
            ->where('requests.0.id', $request->id)
            ->where('requests.0.note', 'سؤال')
            ->where('requests.0.student.whatsapp', fn ($url) => str_starts_with($url, 'https://wa.me/218')));

        $this->get('/teach')->assertInertia(fn (Assert $page) => $page->where('auth.user.pendingRequests', 1));

        $this->post("/teach/enrollments/{$request->id}/accept")->assertSessionHasNoErrors();
        $request->refresh();
        $this->assertSame(Enrollment::STATUS_ACCEPTED, $request->status);
        $this->assertSame('250.500', $request->fee);
        $this->assertNotNull($request->accepted_at);

        // A later price change does not touch the fee.
        $class->forceFill(['price' => '400'])->save();
        $this->assertSame('250.500', $request->fresh()->fee);

        $this->post("/teach/enrollments/{$request->id}/accept")->assertSessionHasErrors(['enrollment' => __('enrollments.already_decided')]);
        $this->get('/teach/requests')->assertInertia(fn (Assert $page) => $page->has('requests', 0));
    }

    public function test_a_student_accepted_again_keeps_the_first_fee(): void
    {
        [$owner, $class] = $this->classWithOwner(['price' => '300']);
        $enrollment = Enrollment::factory()->for($class, 'teachingClass')->accepted('300.000')->create();

        $this->actingAs($owner)->post("/teach/enrollments/{$enrollment->id}/remove", ['reason' => 'غياب']);
        $this->assertSame(Enrollment::STATUS_CANCELED, $enrollment->fresh()->status);
        $this->assertSame('غياب', $enrollment->fresh()->decision_reason);

        $class->forceFill(['price' => '500'])->save();
        $this->actingAs($enrollment->user)->post("/classes/{$class->id}/request");
        $this->actingAs($owner)->post("/teach/enrollments/{$enrollment->id}/accept");

        $this->assertSame(Enrollment::STATUS_ACCEPTED, $enrollment->fresh()->status);
        $this->assertSame('300.000', $enrollment->fresh()->fee);
    }

    public function test_reject_with_an_optional_reason_and_email_the_student(): void
    {
        Notification::fake();
        [$owner, $class] = $this->classWithOwner();
        $student = User::factory()->withEmail()->create();
        $enrollment = Enrollment::factory()->for($class, 'teachingClass')->for($student)->create();

        $this->actingAs($owner)->post("/teach/enrollments/{$enrollment->id}/reject", ['reason' => 'الفصل ممتلئ'])
            ->assertSessionHas('status', __('enrollments.rejected'));

        $this->assertSame(Enrollment::STATUS_REJECTED, $enrollment->fresh()->status);
        $this->assertSame('الفصل ممتلئ', $enrollment->fresh()->decision_reason);
        Notification::assertSentTo($student, EnrollmentDecided::class);

        $this->post("/teach/enrollments/{$enrollment->id}/remove")->assertSessionHasErrors('enrollment');
    }

    public function test_the_class_students_page_lists_only_that_class(): void
    {
        [$owner, $class] = $this->classWithOwner();
        Enrollment::factory()->for($class, 'teachingClass')->accepted()->create();
        Enrollment::factory()->for($class, 'teachingClass')->create();
        Enrollment::factory()->accepted()->create(); // another instructor's class

        $this->actingAs($owner)->get("/teach/classes/{$class->id}/students")->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teach/Classes/Students')
                ->has('enrollments', 2)
                ->where('enrollments.0.status', Enrollment::STATUS_REQUESTED)
                ->where('enrollments.1.status', Enrollment::STATUS_ACCEPTED));
    }

    public function test_another_instructor_cannot_see_or_decide_these_requests(): void
    {
        [, $class] = $this->classWithOwner();
        $request = Enrollment::factory()->for($class, 'teachingClass')->create();
        $accepted = Enrollment::factory()->for($class, 'teachingClass')->accepted()->create();

        [$other] = $this->classWithOwner();
        $this->actingAs($other);

        $this->get('/teach/requests')->assertInertia(fn (Assert $page) => $page->has('requests', 0));
        $this->get('/teach')->assertInertia(fn (Assert $page) => $page->where('auth.user.pendingRequests', 0));
        $this->get("/teach/classes/{$class->id}/students")->assertNotFound();
        $this->post("/teach/enrollments/{$request->id}/accept")->assertForbidden();
        $this->post("/teach/enrollments/{$request->id}/reject")->assertForbidden();
        $this->post("/teach/enrollments/{$accepted->id}/remove")->assertForbidden();
        $this->post("/teach/enrollments/{$accepted->id}/reset-link")->assertForbidden();

        // A student cannot either.
        $this->actingAs($accepted->user)->post("/teach/enrollments/{$request->id}/accept")->assertForbidden();

        $this->assertSame(Enrollment::STATUS_REQUESTED, $request->fresh()->status);
        $this->assertSame(Enrollment::STATUS_ACCEPTED, $accepted->fresh()->status);
        $this->assertSame(0, PasswordResetLink::query()->count());
    }

    public function test_requests_of_a_canceled_class_cannot_be_accepted(): void
    {
        [$owner, $class] = $this->classWithOwner();
        $request = Enrollment::factory()->for($class, 'teachingClass')->create();
        $class->forceFill(['status' => TeachingClass::STATUS_CANCELED])->save();

        $this->actingAs($owner)->get('/teach/requests')->assertInertia(fn (Assert $page) => $page->has('requests', 0));
        $this->post("/teach/enrollments/{$request->id}/accept")->assertSessionHasErrors(['enrollment' => __('enrollments.class_canceled')]);
        $this->assertSame(Enrollment::STATUS_REQUESTED, $request->fresh()->status);
    }

    public function test_reset_links_for_accepted_students_only(): void
    {
        [$owner, $class] = $this->classWithOwner();
        $accepted = Enrollment::factory()->for($class, 'teachingClass')->accepted()->create();
        $requested = Enrollment::factory()->for($class, 'teachingClass')->create();
        $instructorStudent = Enrollment::factory()->for($class, 'teachingClass')->accepted()
            ->for(User::factory()->instructor())->create();

        $this->actingAs($owner);

        $this->post("/teach/enrollments/{$accepted->id}/reset-link")
            ->assertSessionHas('resetLink', fn ($link) => str_contains($link['url'], '/reset/')
                && str_starts_with($link['whatsapp'], 'https://wa.me/218'));
        $link = PasswordResetLink::query()->sole();
        $this->assertSame($accepted->user_id, $link->user_id);
        $this->assertSame(PasswordResetLink::ISSUER_INSTRUCTOR, $link->issued_by_type);
        $this->assertSame($owner->instructor->id, $link->issued_by_id);

        foreach ([$requested, $instructorStudent] as $enrollment) {
            $this->post("/teach/enrollments/{$enrollment->id}/reset-link")
                ->assertSessionHasErrors(['enrollment' => __('enrollments.reset_only_students')]);
        }

        $this->assertSame(1, PasswordResetLink::query()->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: User, 1: TeachingClass}
     */
    private function classWithOwner(array $attributes = []): array
    {
        $owner = User::factory()->instructor()->create();
        $owner->instructor->forceFill(['slug' => 'teacher-'.$owner->id])->save();
        $class = TeachingClass::factory()->published()->for($owner->instructor)->create($attributes);

        return [$owner, $class];
    }
}
