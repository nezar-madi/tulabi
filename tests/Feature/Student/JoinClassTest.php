<?php

namespace Tests\Feature\Student;

use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Models\User;
use App\Notifications\EnrollmentRequested;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JoinClassTest extends TestCase
{
    public function test_a_student_asks_to_join_and_sees_it_in_my_classes(): void
    {
        $class = $this->publishedClass();
        $student = User::factory()->create();

        $this->actingAs($student)->get($this->classUrl($class))->assertInertia(fn (Assert $page) => $page
            ->where('acceptsRequests', true)
            ->where('enrollment', null));

        $this->post("/classes/{$class->id}/request", [
            'note' => 'مستواي متوسط',
            'status' => Enrollment::STATUS_ACCEPTED,
            'fee' => 0,
        ])->assertSessionHas('status', __('enrollments.requested'));

        $enrollment = Enrollment::query()->sole();
        $this->assertSame(Enrollment::STATUS_REQUESTED, $enrollment->status);
        $this->assertSame($student->id, $enrollment->user_id);
        $this->assertSame('مستواي متوسط', $enrollment->note);
        $this->assertNull($enrollment->fee);

        $this->get($this->classUrl($class))->assertInertia(fn (Assert $page) => $page
            ->where('enrollment.status', Enrollment::STATUS_REQUESTED));

        $this->get('/account/classes')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Student/Classes/Index')
            ->has('enrollments', 1)
            ->where('enrollments.0.status', Enrollment::STATUS_REQUESTED));

        // Asking twice does nothing.
        $this->post("/classes/{$class->id}/request")
            ->assertSessionHasErrors(['enrollment' => __('enrollments.already_requested')]);
        $this->assertSame(1, Enrollment::query()->count());
    }

    public function test_a_visitor_is_sent_to_sign_in_and_back_to_the_class(): void
    {
        $class = $this->publishedClass();

        $this->get("/classes/{$class->id}/request")->assertRedirect('/login');
        $this->post("/classes/{$class->id}/request")->assertRedirect('/login');

        $student = User::factory()->create(['phone' => '+218911111111']);
        $this->post('/login', ['phone' => '0911111111', 'password' => 'password'])
            ->assertRedirect("/classes/{$class->id}/request");
        $this->get("/classes/{$class->id}/request")->assertRedirect($this->classUrl($class).'?request=1');

        $this->assertSame(0, $student->enrollments()->count());
    }

    public function test_only_open_classes_take_requests(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student);

        $draft = TeachingClass::factory()->for($this->instructor())->create();
        $closed = $this->publishedClass();
        $closed->forceFill(['status' => TeachingClass::STATUS_CLOSED])->save();
        $canceled = $this->publishedClass();
        $canceled->forceFill(['status' => TeachingClass::STATUS_CANCELED])->save();
        $ended = $this->publishedClass(['starts_on' => now()->subMonths(3)->toDateString(), 'ends_on' => now()->subMonth()->toDateString()]);
        $hiddenInstructor = $this->publishedClass();
        $hiddenInstructor->instructor->forceFill(['status' => Instructor::STATUS_SUSPENDED])->save();

        foreach ([$draft, $closed, $canceled, $ended, $hiddenInstructor] as $class) {
            $this->post("/classes/{$class->id}/request")->assertSessionHasErrors(['enrollment' => __('enrollments.not_open')]);
        }

        $this->assertSame(0, Enrollment::query()->count());
    }

    public function test_an_instructor_cannot_join_their_own_class_but_can_join_others(): void
    {
        $class = $this->publishedClass();
        $owner = $class->instructor->user;

        $this->actingAs($owner)->post("/classes/{$class->id}/request")
            ->assertSessionHasErrors(['enrollment' => __('enrollments.own_class')]);

        $other = $this->publishedClass();
        $this->post("/classes/{$other->id}/request")->assertSessionHasNoErrors();
        $this->assertSame($owner->id, Enrollment::query()->sole()->user_id);
    }

    public function test_withdraw_and_ask_again_on_the_same_row(): void
    {
        $class = $this->publishedClass();
        $student = User::factory()->create();
        $this->actingAs($student)->post("/classes/{$class->id}/request");
        $enrollment = Enrollment::query()->sole();

        $this->post("/account/classes/{$enrollment->id}/withdraw")->assertSessionHas('status', __('enrollments.withdrawn'));
        $this->assertSame(Enrollment::STATUS_WITHDRAWN, $enrollment->fresh()->status);

        $this->post("/account/classes/{$enrollment->id}/withdraw")->assertSessionHasErrors('enrollment');

        $this->post("/classes/{$class->id}/request", ['note' => 'مرة ثانية'])->assertSessionHasNoErrors();
        $this->assertSame(1, Enrollment::query()->count());
        $this->assertSame(Enrollment::STATUS_REQUESTED, $enrollment->fresh()->status);
        $this->assertSame('مرة ثانية', $enrollment->fresh()->note);
    }

    public function test_the_meeting_link_is_for_accepted_students_of_a_running_class_only(): void
    {
        $class = $this->publishedClass(['type' => TeachingClass::TYPE_ONLINE, 'location' => null, 'meeting_url' => 'https://meet.google.com/xyz']);
        $student = User::factory()->create();
        $enrollment = Enrollment::factory()->for($class, 'teachingClass')->for($student)->create();

        $this->actingAs($student);
        $this->get("/account/classes/{$enrollment->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('enrollment.class.meeting_url', null))
            ->assertDontSee('meet.google.com/xyz', false);

        $enrollment->forceFill(['status' => Enrollment::STATUS_ACCEPTED, 'fee' => '300'])->save();
        $this->get("/account/classes/{$enrollment->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('enrollment.status', Enrollment::STATUS_ACCEPTED)
                ->where('enrollment.class.meeting_url', 'https://meet.google.com/xyz'));

        // A canceled class: the place shows «ملغى» and the link is gone.
        $class->forceFill(['status' => TeachingClass::STATUS_CANCELED])->save();
        $this->get("/account/classes/{$enrollment->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('enrollment.status', Enrollment::STATUS_CANCELED)
                ->where('enrollment.class.meeting_url', null));
        $this->assertSame(Enrollment::STATUS_ACCEPTED, $enrollment->fresh()->status);
    }

    public function test_a_student_sees_only_their_own_enrollments(): void
    {
        $mine = Enrollment::factory()->accepted()->create();
        $theirs = Enrollment::factory()->accepted()->create();

        $this->actingAs($mine->user);
        $this->get('/account/classes')->assertInertia(fn (Assert $page) => $page
            ->has('enrollments', 1)
            ->where('enrollments.0.id', $mine->id));

        $this->get("/account/classes/{$theirs->id}")->assertForbidden();
        $this->post("/account/classes/{$theirs->id}/withdraw")->assertForbidden();
        $this->assertSame(Enrollment::STATUS_ACCEPTED, $theirs->fresh()->status);
    }

    public function test_the_instructor_is_emailed_only_when_they_have_an_email(): void
    {
        Notification::fake();
        $class = $this->publishedClass();

        $this->actingAs(User::factory()->create())->post("/classes/{$class->id}/request");
        Notification::assertNothingSent();

        $withEmail = $this->publishedClass();
        $withEmail->instructor->user->forceFill(['email' => 'teacher@example.com'])->save();
        $this->post("/classes/{$withEmail->id}/request");
        Notification::assertSentTo($withEmail->instructor->user, EnrollmentRequested::class);
    }

    private function instructor(): Instructor
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => 'teacher-'.$user->id])->save();

        return $user->instructor;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function publishedClass(array $attributes = []): TeachingClass
    {
        return TeachingClass::factory()->published()->for($this->instructor())->create($attributes);
    }

    private function classUrl(TeachingClass $class): string
    {
        return '/'.$class->instructor->slug.'/classes/'.$class->id;
    }
}
