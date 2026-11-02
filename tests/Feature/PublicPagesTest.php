<?php

namespace Tests\Feature;

use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\InstructorSlug;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-10 10:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_instructor_page_shows_only_open_classes(): void
    {
        $instructor = $this->instructor('ahmed');
        $shown = TeachingClass::factory()->published()->for($instructor)->create(['title' => 'منشور']);
        $closed = TeachingClass::factory()->for($instructor)->create(['title' => 'مغلق'])
            ->forceFill(['status' => TeachingClass::STATUS_CLOSED]);
        $closed->save();
        TeachingClass::factory()->for($instructor)->create(['title' => 'مسودة']);
        TeachingClass::factory()->published()->for($instructor)->create(['title' => 'ملغى'])
            ->forceFill(['status' => TeachingClass::STATUS_CANCELED])->save();
        TeachingClass::factory()->published()->for($instructor)->create([
            'title' => 'منتهٍ', 'starts_on' => '2026-09-01', 'ends_on' => '2026-10-30',
        ]);
        TeachingClass::factory()->published()->for($this->instructor('other'))->create(['title' => 'لمدرّس آخر']);

        $response = $this->get('/ahmed')->assertOk()->assertViewIs('site.instructor');
        $response->assertViewHas('site', fn ($site) => $site['slug'] === 'ahmed'
            && str_starts_with($site['whatsapp_url'], 'https://wa.me/218')
            && $site['is_owner'] === false);
        $response->assertViewHas('courses', fn ($courses) => array_column($courses, 'id') === [$shown->id, $closed->id]
            && ! array_key_exists('meeting_url', $courses[0]));
        $response->assertSee('منشور')->assertSee('مغلق')
            ->assertDontSee('مسودة</a>', false)->assertDontSee('ملغى</a>', false)
            ->assertDontSee('منتهٍ')->assertDontSee('لمدرّس آخر');
    }

    public function test_the_class_page_never_shows_the_meeting_link(): void
    {
        $instructor = $this->instructor('ahmed');
        $class = TeachingClass::factory()->online()->published()->for($instructor)->create();

        $response = $this->get("/ahmed/classes/{$class->id}")->assertOk()->assertViewIs('site.class');
        $response->assertViewHas('course', fn ($course) => $course['id'] === $class->id
            && $course['type'] === 'online'
            && ! array_key_exists('meeting_url', $course));
        $response->assertDontSee('meet.google.com', false);
    }

    public function test_drafts_are_visible_to_their_owner_only_and_canceled_classes_to_nobody(): void
    {
        $instructor = $this->instructor('ahmed');
        $draft = TeachingClass::factory()->for($instructor)->create();
        $canceled = TeachingClass::factory()->for($instructor)->create();
        $canceled->forceFill(['status' => TeachingClass::STATUS_CANCELED])->save();

        $this->get("/ahmed/classes/{$draft->id}")->assertNotFound();
        $this->actingAs(User::factory()->create())->get("/ahmed/classes/{$draft->id}")->assertNotFound();

        $this->actingAs($instructor->user)->get("/ahmed/classes/{$draft->id}")
            ->assertOk()
            ->assertViewHas('site', fn ($site) => $site['is_owner'] === true)
            ->assertViewHas('course', fn ($course) => $course['status'] === 'draft')
            ->assertSee(__('site.draft_bar'));
        $this->get("/ahmed/classes/{$canceled->id}")->assertNotFound();
    }

    public function test_a_class_is_only_found_under_its_own_instructor(): void
    {
        $class = TeachingClass::factory()->published()->for($this->instructor('ahmed'))->create();
        $this->instructor('other');

        $this->get("/other/classes/{$class->id}")->assertNotFound();
        $this->get('/ahmed/classes/999999')->assertNotFound();
    }

    public function test_pages_of_hidden_instructors_are_not_found(): void
    {
        $noSlug = $this->instructor(null);
        $this->get('/nobody-here')->assertNotFound();

        $suspendedAccount = $this->instructor('suspended-account');
        $suspendedAccount->user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
        $this->get('/suspended-account')->assertNotFound();

        $teachingOff = $this->instructor('teaching-off');
        $teachingOff->forceFill(['status' => Instructor::STATUS_SUSPENDED])->save();
        $this->get('/teaching-off')->assertNotFound();

        $this->assertNull($noSlug->publicUrl());
    }

    public function test_the_site_paths_still_work_and_are_reserved(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
        $this->get('/join')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Join/Create'));
        $this->get('/admin')->assertRedirect('/admin/login');

        // Every first path segment of every route must be reserved, or a slug could shadow it.
        $segments = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => Str::before(ltrim($route->uri(), '/'), '/'))
            ->filter(fn (string $segment) => $segment !== '' && ! str_starts_with($segment, '{'))
            ->unique()
            ->values();

        foreach ($segments as $segment) {
            $this->assertTrue(InstructorSlug::isReserved($segment), "Add '{$segment}' to InstructorSlug::RESERVED.");
        }
    }

    private function instructor(?string $slug): Instructor
    {
        $user = User::factory()->instructor()->create();
        $instructor = $user->instructor;
        $instructor->forceFill(['slug' => $slug, 'whatsapp' => $user->phone])->save();

        return $instructor;
    }
}
