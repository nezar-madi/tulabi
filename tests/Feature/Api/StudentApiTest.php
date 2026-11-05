<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Program;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\Payments;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    public function test_a_visitor_sees_an_instructors_page_with_full_links(): void
    {
        $instructor = $this->instructor();
        $instructor->forceFill(['photo_path' => 'instructor-photos/1/me.jpg'])->save();
        $open = $this->publishedClass($instructor, ['title' => 'رياضيات مفتوحة', 'image_path' => 'class-images/1/x.jpg']);
        TeachingClass::factory()->for($instructor)->create(['title' => 'مسودة']);
        Program::factory()->for($instructor)->create([
            'name' => 'برنامج ظاهر', 'is_published' => true, 'promo_video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ]);
        Program::factory()->for($instructor)->create(['name' => 'برنامج مخفي', 'is_published' => false]);

        $response = $this->getJson("/api/v1/instructors/{$instructor->slug}")
            ->assertOk()
            ->assertJsonPath('instructor.slug', $instructor->slug)
            ->assertJsonPath('instructor.photo_url', url('/storage/instructor-photos/1/me.jpg'))
            ->assertJsonCount(1, 'classes')
            ->assertJsonPath('classes.0.id', $open->id)
            ->assertJsonPath('classes.0.image_url', url('/storage/class-images/1/x.jpg'))
            ->assertJsonCount(1, 'programs')
            ->assertJsonPath('programs.0.name', 'برنامج ظاهر')
            ->assertJsonMissingPath('instructor.is_owner')
            ->assertJsonMissingPath('classes.0.request_url');

        $this->assertIsString($response->json('classes.0.starts_on'));
    }

    public function test_hidden_things_are_not_found(): void
    {
        $instructor = $this->instructor();
        $draft = TeachingClass::factory()->for($instructor)->create();
        $hidden = Program::factory()->for($instructor)->create(['is_published' => false]);

        $this->getJson("/api/v1/instructors/{$instructor->slug}/classes/{$draft->id}")->assertNotFound();
        $this->getJson("/api/v1/instructors/{$instructor->slug}/programs/{$hidden->id}")->assertNotFound();
        $this->getJson('/api/v1/instructors/nobody-here')->assertNotFound();
        $this->getJson("/api/v1/instructors/{$instructor->slug}/classes/99999999999999999999")->assertNotFound();

        // Even the owner sees visitors' rules here (drafts are managed in the instructor part).
        $token = $instructor->user->createToken('phone')->plainTextToken;
        $this->getJson("/api/v1/instructors/{$instructor->slug}/classes/{$draft->id}", ['Authorization' => 'Bearer '.$token])
            ->assertNotFound();
    }

    public function test_a_program_page_lists_its_open_classes(): void
    {
        $instructor = $this->instructor();
        $program = Program::factory()->for($instructor)->create(['is_published' => true, 'promo_video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $run = $this->publishedClass($instructor, ['program_id' => $program->id]);
        $this->publishedClass($instructor);

        $this->getJson("/api/v1/instructors/{$instructor->slug}/programs/{$program->id}")
            ->assertOk()
            ->assertJsonPath('program.id', $program->id)
            ->assertJsonPath('program.upcoming_count', 1)
            ->assertJsonPath('program.video_embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->assertJsonCount(1, 'classes')
            ->assertJsonPath('classes.0.id', $run->id);
    }

    public function test_a_student_asks_to_join_and_sees_it_in_their_classes(): void
    {
        $class = $this->publishedClass($this->instructor());
        $student = User::factory()->create();
        $auth = $this->bearer($student);
        $url = "/api/v1/instructors/{$class->instructor->slug}/classes/{$class->id}";

        $this->getJson($url, $auth)->assertOk()->assertJsonPath('class.enrollment', null)->assertJsonPath('class.is_open', true);

        $this->postJson("/api/v1/classes/{$class->id}/join", ['note' => 'مستواي متوسط', 'status' => 'accepted'], $auth)
            ->assertCreated()
            ->assertJsonPath('enrollment.status', Enrollment::STATUS_REQUESTED)
            ->assertJsonPath('enrollment.class.meeting_url', null);

        $this->assertSame(Enrollment::STATUS_REQUESTED, $student->enrollments()->sole()->status);
        $this->getJson($url, $auth)->assertJsonPath('class.enrollment.status', Enrollment::STATUS_REQUESTED);

        $this->postJson("/api/v1/classes/{$class->id}/join", [], $auth)
            ->assertJsonValidationErrors(['enrollment' => __('enrollments.already_requested')]);

        $this->getJson('/api/v1/student/classes', $auth)
            ->assertOk()
            ->assertJsonCount(1, 'enrollments')
            ->assertJsonPath('enrollments.0.class.id', $class->id);
    }

    public function test_joining_needs_a_token_and_an_open_visible_class(): void
    {
        $instructor = $this->instructor();
        $draft = TeachingClass::factory()->for($instructor)->create();
        $auth = $this->bearer(User::factory()->create());

        $this->postJson("/api/v1/classes/{$draft->id}/join")->assertUnauthorized();
        $this->postJson("/api/v1/classes/{$draft->id}/join", [], $auth)->assertNotFound();

        $own = $this->publishedClass($instructor);
        $this->postJson("/api/v1/classes/{$own->id}/join", [], $this->bearer($instructor->user))
            ->assertJsonValidationErrors(['enrollment' => __('enrollments.own_class')]);
        $this->assertSame(0, Enrollment::query()->count());
    }

    public function test_the_meeting_link_and_payments_are_for_the_accepted_student_only(): void
    {
        $instructor = $this->instructor();
        $class = $this->publishedClass($instructor, ['type' => TeachingClass::TYPE_ONLINE, 'meeting_url' => 'https://meet.example/abc']);
        $student = User::factory()->create();
        $enrollment = Enrollment::factory()->accepted('300.000')->for($class, 'teachingClass')->for($student)->create();
        $payment = app(Payments::class)->record($enrollment, ['amount' => '120', 'paid_at' => now()->toDateString(), 'method' => 'cash'], $instructor->user);

        $auth = $this->bearer($student);

        $this->getJson("/api/v1/student/classes/{$enrollment->id}", $auth)
            ->assertOk()
            ->assertJsonPath('enrollment.class.meeting_url', 'https://meet.example/abc')
            ->assertJsonPath('totals.paid', '120')
            ->assertJsonPath('totals.remaining', '180')
            ->assertJsonPath('payments.0.receipt_url', url("/api/v1/receipts/{$payment->id}"));

        $this->getJson('/api/v1/student/payments', $auth)
            ->assertOk()
            ->assertJsonCount(1, 'payments')
            ->assertJsonPath('payments.0.class_title', $class->title)
            ->assertJsonPath('payments.0.enrollment_id', $enrollment->id);

        $this->get("/api/v1/receipts/{$payment->id}", $auth)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get("/api/v1/receipts/{$payment->id}", $this->bearer($instructor->user))->assertOk();

        // Someone else: not their enrollment, not their receipt, no payments.
        $other = $this->bearer(User::factory()->create());
        $this->getJson("/api/v1/student/classes/{$enrollment->id}", $other)->assertForbidden();
        $this->postJson("/api/v1/student/classes/{$enrollment->id}/withdraw", [], $other)->assertForbidden();
        $this->getJson("/api/v1/receipts/{$payment->id}", $other)->assertForbidden();
        $this->getJson('/api/v1/student/payments', $other)->assertJsonCount(0, 'payments');
    }

    public function test_a_student_takes_back_a_request_or_leaves_a_class_once(): void
    {
        $class = $this->publishedClass($this->instructor());
        $student = User::factory()->create();
        $waiting = Enrollment::factory()->for($class, 'teachingClass')->for($student)->create();
        $accepted = Enrollment::factory()->accepted()->for($this->publishedClass($class->instructor), 'teachingClass')->for($student)->create();
        $auth = $this->bearer($student);

        foreach ([$waiting, $accepted] as $enrollment) {
            $this->postJson("/api/v1/student/classes/{$enrollment->id}/withdraw", [], $auth)
                ->assertOk()
                ->assertJsonPath('message', __('enrollments.withdrawn'))
                ->assertJsonPath('enrollment.status', Enrollment::STATUS_WITHDRAWN);
            $this->assertSame(Enrollment::STATUS_WITHDRAWN, $enrollment->fresh()->status);

            // A second time: nothing left to take back.
            $this->postJson("/api/v1/student/classes/{$enrollment->id}/withdraw", [], $auth)
                ->assertJsonValidationErrors(['enrollment' => __('enrollments.already_decided')]);
        }
    }

    public function test_the_student_routes_need_a_token(): void
    {
        $this->getJson('/api/v1/student/classes')->assertUnauthorized();
        $this->getJson('/api/v1/student/payments')->assertUnauthorized();
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function publishedClass(Instructor $instructor, array $attributes = []): TeachingClass
    {
        return TeachingClass::factory()->published()->for($instructor)->create($attributes);
    }
}
