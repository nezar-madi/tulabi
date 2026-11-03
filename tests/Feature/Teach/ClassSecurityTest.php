<?php

namespace Tests\Feature\Teach;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\Enrollments;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Findings of the classes security audit (2026-11-03). */
class ClassSecurityTest extends TestCase
{
    public function test_no_page_renders_a_description_as_html(): void
    {
        // The descriptions are plain text kept as typed: a v-html anywhere would run an instructor's markup.
        $offenders = collect(File::allFiles(resource_path('js')))
            ->filter(fn ($file) => str_contains($file->getContents(), 'v-html'))
            ->map(fn ($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $offenders);
    }

    public function test_the_student_page_gets_the_description_as_typed_text(): void
    {
        $class = TeachingClass::factory()->published()->create(['description' => "<img src=x onerror=alert(1)>\nسطر"]);
        $enrollment = Enrollment::factory()->for($class, 'teachingClass')->create();

        // The page receives the text as is; Show.vue prints it with {{ }} (escaped).
        $this->actingAs($enrollment->user)->get("/account/classes/{$enrollment->id}")
            ->assertInertia(fn (Assert $page) => $page->where('enrollment.class.description', "<img src=x onerror=alert(1)>\nسطر"));
    }

    public function test_saving_classes_is_rate_limited(): void
    {
        $user = User::factory()->instructor()->create();
        $this->actingAs($user);

        for ($i = 0; $i < 30; $i++) {
            $this->post('/teach/classes', [])->assertStatus(302);
        }

        $this->post('/teach/classes', [])->assertStatus(429);
    }

    public function test_the_join_link_reveals_nothing_about_a_class_visitors_cannot_see(): void
    {
        $teacher = User::factory()->instructor()->create();
        $teacher->instructor->forceFill(['slug' => 'hidden-teacher'])->save();
        $draft = TeachingClass::factory()->for($teacher->instructor)->create();
        $visitor = User::factory()->create();

        $this->actingAs($visitor)->get("/classes/{$draft->id}/request")->assertNotFound();

        $public = TeachingClass::factory()->published()->for($teacher->instructor)->create();
        $this->get("/classes/{$public->id}/request")->assertRedirect("/hidden-teacher/classes/{$public->id}?request=1");

        // A hidden instructor's page address stays hidden too.
        $teacher->instructor->forceFill(['status' => 'inactive'])->save();
        $this->get("/classes/{$public->id}/request")->assertNotFound();
    }

    public function test_a_second_request_racing_the_first_gets_a_message_not_an_error(): void
    {
        $teacher = User::factory()->instructor()->create();
        $teacher->instructor->forceFill(['slug' => 'ahmed'])->save();
        $class = TeachingClass::factory()->published()->for($teacher->instructor)->create();
        $student = User::factory()->create();

        // The other request lands between our check and our insert.
        DB::listen(function ($query) use ($class, $student) {
            static $done = false;

            if (! $done && str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'enrollments')) {
                $done = true;
                DB::table('enrollments')->insert([
                    'class_id' => $class->id, 'user_id' => $student->id, 'status' => Enrollment::STATUS_REQUESTED,
                    'currency_code' => 'LYD', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        try {
            app(Enrollments::class)->request($student, $class, null);
            $this->fail('A second request should be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame(__('enrollments.already_requested'), $exception->errors()['enrollment'][0]);
        } catch (UniqueConstraintViolationException) {
            $this->fail('The database error reached the user.');
        }
    }

    public function test_the_classes_list_does_not_ask_the_database_once_per_class(): void
    {
        $user = User::factory()->instructor()->create();
        $this->actingAs($user);

        TeachingClass::factory()->published()->for($user->instructor)->count(2)->create();
        DB::enableQueryLog();
        $this->get('/teach/classes')->assertOk();
        $few = count(DB::getQueryLog());

        TeachingClass::factory()->published()->for($user->instructor)->count(18)->create();
        DB::flushQueryLog();
        $this->get('/teach/classes')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('classes.data', 20)->where('classes.data.0.price_locked', false));

        $this->assertSame($few, count(DB::getQueryLog()));
    }

    public function test_restarting_a_class_with_students_offers_the_new_schedule_on_whatsapp(): void
    {
        $this->travelTo('2026-12-15 10:00:00');
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => 'ahmed'])->save();
        $class = TeachingClass::factory()->for($user->instructor)->create([
            'status' => TeachingClass::STATUS_SUSPENDED, 'starts_on' => '2026-12-10', 'ends_on' => '2027-01-31',
            'schedule_type' => 'daily', 'days' => null,
        ]);
        Enrollment::factory()->accepted()->for($class, 'teachingClass')->create();

        $this->actingAs($user)->from("/teach/classes/{$class->id}/edit")
            ->post("/teach/classes/{$class->id}/reactivate", ['starts_on' => '2026-12-20'])
            ->assertSessionHas('scheduleChanged', true);
        $this->get("/teach/classes/{$class->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->where('scheduleShareUrl', fn ($url) => str_starts_with((string) $url, 'https://wa.me/?text=')));
    }
}
