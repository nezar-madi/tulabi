<?php

namespace Tests\Feature\Teach;

use App\Models\Enrollment;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\TulabiClock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClassTest extends TestCase
{
    public function test_a_new_class_is_a_draft_with_plain_text_content(): void
    {
        $user = $this->instructor();

        $this->actingAs($user)->get('/teach/classes/create')->assertOk();

        $response = $this->post('/teach/classes', $this->data([
            'description' => "<script>alert(1)</script>\r\nسطر ثان\r\n\r\n\r\n\r\nفقرة",
            'learn' => ['  حل المعادلات  ', '', 'التفاضل', '   '],
            'requirements' => ['جبر أساسي'],
            'audience' => ['', 'طلاب الثانوي'],
            'status' => TeachingClass::STATUS_PUBLISHED,
            'instructor_id' => 999,
            'currency_code' => 'USD',
            'days' => [3, 6, 6, 1],
        ]));

        $class = TeachingClass::query()->sole();
        $response->assertRedirect("/teach/classes/{$class->id}/edit");

        $this->assertSame(TeachingClass::STATUS_DRAFT, $class->status);
        $this->assertSame($user->instructor->id, $class->instructor_id);
        $this->assertSame('LYD', $class->currency_code);
        $this->assertSame([1, 3, 6], $class->days);
        // Plain text: kept as typed (the pages escape it), line breaks normalised, blank runs squeezed.
        $this->assertSame("<script>alert(1)</script>\nسطر ثان\n\nفقرة", $class->description);
        $this->assertSame(['حل المعادلات', 'التفاضل'], $class->learn);
        $this->assertSame(['جبر أساسي'], $class->requirements);
        $this->assertSame(['طلاب الثانوي'], $class->audience);
        $this->assertSame('2026-12-01', $class->starts_on->toDateString());
    }

    public function test_validation(): void
    {
        $this->actingAs($this->instructor());

        $this->post('/teach/classes', $this->data(['type' => 'in_person', 'map_url' => '']))
            ->assertSessionHasErrors('map_url');
        $this->post('/teach/classes', $this->data(['description' => '']))->assertSessionHasErrors('description');
        $this->post('/teach/classes', $this->data(['description' => " \n  \n "]))->assertSessionHasErrors('description');
        $this->post('/teach/classes', $this->data(['description' => str_repeat('ا', 5001)]))->assertSessionHasErrors('description');
        $this->post('/teach/classes', $this->data(['learn' => array_fill(0, 16, 'نقطة')]))->assertSessionHasErrors('learn');
        $this->post('/teach/classes', $this->data(['learn' => [str_repeat('ا', 201)]]))->assertSessionHasErrors('learn.0');
        $this->post('/teach/classes', $this->data(['ends_on' => '2026-11-01']))
            ->assertSessionHasErrors('ends_on');
        $this->post('/teach/classes', $this->data(['start_time' => '18:00', 'end_time' => '17:00']))
            ->assertSessionHasErrors(['end_time' => __('classes.end_time_after')]);
        $this->post('/teach/classes', $this->data(['price' => '-5']))->assertSessionHasErrors('price');
        $this->post('/teach/classes', $this->data(['price' => '10.1234']))->assertSessionHasErrors('price');
        $this->post('/teach/classes', $this->data(['days' => [7]]))->assertSessionHasErrors('days.0');
        $this->post('/teach/classes', $this->data(['type' => 'online', 'meeting_url' => 'not a url']))
            ->assertSessionHasErrors('meeting_url');

        $this->assertSame(0, TeachingClass::query()->count());
    }

    public function test_the_required_fields_of_a_class(): void
    {
        $this->actingAs($this->instructor());

        // Each list needs one real item (blank items are dropped first).
        foreach (['learn', 'requirements', 'audience'] as $list) {
            $this->post('/teach/classes', $this->data([$list => []]))
                ->assertSessionHasErrors([$list => __('classes.list_required')]);
            $this->post('/teach/classes', $this->data([$list => ['  ', '']]))
                ->assertSessionHasErrors([$list => __('classes.list_required')]);
        }

        foreach (['ends_on', 'start_time', 'end_time', 'seats', 'price'] as $field) {
            $this->post('/teach/classes', $this->data([$field => '']))->assertSessionHasErrors($field);
        }

        $this->post('/teach/classes', $this->data(['seats' => '-1']))->assertSessionHasErrors('seats');
        // «من برنامج تدريبي» without a program.
        $this->post('/teach/classes', $this->data(['from_program' => '1', 'program_id' => '']))
            ->assertSessionHasErrors('program_id');
        $this->assertSame(0, TeachingClass::query()->count());

        // 0 seats = no limit, 0 = free; the video and the written place stay optional.
        $this->post('/teach/classes', $this->data([
            'from_program' => '0', 'seats' => '0', 'price' => '0', 'promo_video_url' => '', 'location' => '',
        ]))->assertSessionHasNoErrors();
        $class = TeachingClass::query()->sole();
        $this->assertNull($class->seats);
        $this->assertSame('0.000', (string) $class->price);
    }

    public function test_daily_weekly_and_manual_schedules(): void
    {
        $this->actingAs($this->instructor());

        // Daily: no days kept, whatever was sent.
        $this->post('/teach/classes', $this->data(['schedule_type' => 'daily', 'days' => [1, 2], 'manual_dates' => ['2026-12-05']]))
            ->assertSessionHasNoErrors();
        $daily = TeachingClass::query()->latest('id')->first();
        $this->assertSame('daily', $daily->schedule_type);
        $this->assertNull($daily->days);
        $this->assertNull($daily->manual_dates);

        // Weekly needs at least one weekday.
        $this->post('/teach/classes', $this->data(['schedule_type' => 'weekly', 'days' => []]))->assertSessionHasErrors('days');
        $this->post('/teach/classes', $this->data(['schedule_type' => 'weekly', 'days' => [6, 1]]))->assertSessionHasNoErrors();
        $weekly = TeachingClass::query()->latest('id')->first();
        $this->assertSame([1, 6], $weekly->days);
        $this->assertNull($weekly->manual_dates);

        // Manual needs dates inside the period; they are sorted and de-duplicated.
        $this->post('/teach/classes', $this->data(['schedule_type' => 'manual', 'manual_dates' => []]))->assertSessionHasErrors('manual_dates');
        $this->post('/teach/classes', $this->data(['schedule_type' => 'manual', 'manual_dates' => ['2026-11-30']]))
            ->assertSessionHasErrors(['manual_dates' => __('classes.manual_dates_outside_range')]);
        $this->post('/teach/classes', $this->data(['schedule_type' => 'manual', 'manual_dates' => ['2027-02-01']]))
            ->assertSessionHasErrors(['manual_dates' => __('classes.manual_dates_outside_range')]);
        $this->post('/teach/classes', $this->data(['schedule_type' => 'manual', 'manual_dates' => ['12/05/2026']]))
            ->assertSessionHasErrors('manual_dates.0');
        $this->post('/teach/classes', $this->data([
            'schedule_type' => 'manual',
            'days' => [1],
            'manual_dates' => ['2026-12-20', '2026-12-05', '2026-12-05'],
        ]))->assertSessionHasNoErrors();
        $manual = TeachingClass::query()->latest('id')->first();
        $this->assertSame(['2026-12-05', '2026-12-20'], $manual->manual_dates);
        $this->assertNull($manual->days);
        $this->assertSame('2027-01-31', $manual->ends_on->toDateString());

        $this->post('/teach/classes', $this->data(['schedule_type' => 'weekend']))->assertSessionHasErrors('schedule_type');
        $this->post('/teach/classes', $this->data(['schedule_type' => '']))->assertSessionHasErrors('schedule_type');
    }

    public function test_a_short_course_must_meet_on_the_chosen_weekdays(): void
    {
        $this->actingAs($this->instructor());

        // 1 Dec 2026 is a Tuesday; a three-day course never meets on a Friday (5).
        $this->post('/teach/classes', $this->data(['starts_on' => '2026-12-01', 'ends_on' => '2026-12-03', 'days' => [5]]))
            ->assertSessionHasErrors(['days' => __('classes.days_outside_range')]);
        $this->post('/teach/classes', $this->data(['starts_on' => '2026-12-01', 'ends_on' => '2026-12-03', 'days' => [2, 3]]))
            ->assertSessionHasNoErrors();
    }

    public function test_the_place_text_is_optional_but_the_map_link_must_be_google_maps(): void
    {
        $this->actingAs($this->instructor());

        foreach ([
            'http://maps.app.goo.gl/abc',
            'https://evil.example/maps/abc',
            'https://google.com.evil.example/maps/x',
            'https://www.google.com/search?q=maps',
            'https://maps.app.goo.gl/',
            'javascript:alert(1)',
            'مكاني',
        ] as $url) {
            $this->post('/teach/classes', $this->data(['map_url' => $url]))->assertSessionHasErrors('map_url');
        }

        $this->assertSame(0, TeachingClass::query()->count());

        foreach ([
            'https://maps.app.goo.gl/abc123',
            'https://www.google.com/maps/place/Tripoli/@32.9,13.1,12z',
            'https://maps.google.com/?q=32.9,13.1',
            'https://goo.gl/maps/xyz789',
        ] as $url) {
            $this->post('/teach/classes', $this->data(['location' => '', 'map_url' => $url]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(4, TeachingClass::query()->count());
        $this->assertNull(TeachingClass::query()->first()->location);
    }

    public function test_an_online_class_needs_no_map_link_and_drops_one(): void
    {
        $this->actingAs($this->instructor());

        $this->post('/teach/classes', $this->data(['type' => 'online', 'map_url' => 'https://maps.app.goo.gl/abc']))
            ->assertSessionHasNoErrors();

        $this->assertNull(TeachingClass::query()->sole()->map_url);
    }

    public function test_a_promotional_image_is_saved_replaced_and_removed(): void
    {
        Storage::fake('public');
        $this->actingAs($this->instructor());

        $this->post('/teach/classes', $this->data(['image' => UploadedFile::fake()->image('promo.png', 1600, 900)]))
            ->assertSessionHasNoErrors();

        $class = TeachingClass::query()->sole();
        $first = $class->image_path;
        $this->assertStringStartsWith('class-images/'.$class->instructor_id.'/', $first);
        $this->assertStringEndsWith('.jpg', $first);
        Storage::disk('public')->assertExists($first);
        $this->assertSame('/storage/'.$first, $class->imageUrl());

        // Saved as a 16:10 JPEG whatever was uploaded.
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($first));
        $this->assertSame(1200, $width);
        $this->assertSame(750, $height);

        $this->put("/teach/classes/{$class->id}", $this->data(['image' => UploadedFile::fake()->image('new.jpg', 800, 800)]))
            ->assertSessionHasNoErrors();
        $class->refresh();
        $this->assertNotSame($first, $class->image_path);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($class->image_path);

        // Saving without a file keeps the image; asking to remove deletes it.
        $kept = $class->image_path;
        $this->put("/teach/classes/{$class->id}", $this->data());
        $this->assertSame($kept, $class->fresh()->image_path);

        $this->put("/teach/classes/{$class->id}", $this->data(['remove_image' => true]));
        $this->assertNull($class->fresh()->image_path);
        Storage::disk('public')->assertMissing($kept);
    }

    public function test_the_image_must_be_a_real_image_of_a_sensible_size(): void
    {
        Storage::fake('public');
        $this->actingAs($this->instructor());

        $this->post('/teach/classes', $this->data(['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('image');
        $this->post('/teach/classes', $this->data(['image' => UploadedFile::fake()->image('small.png', 100, 100)]))
            ->assertSessionHasErrors('image');
        $this->post('/teach/classes', $this->data(['image' => UploadedFile::fake()->create('big.png', 5000, 'image/png')]))
            ->assertSessionHasErrors('image');

        $this->assertSame(0, TeachingClass::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_image_cannot_be_set_through_other_fields(): void
    {
        $this->actingAs($this->instructor());

        $this->post('/teach/classes', $this->data(['image_path' => 'instructors/1/x.jpg']));

        $this->assertNull(TeachingClass::query()->sole()->image_path);
    }

    public function test_the_other_types_fields_are_emptied(): void
    {
        $this->actingAs($this->instructor());

        $this->post('/teach/classes', $this->data([
            'type' => 'online',
            'location' => 'طرابلس',
            'meeting_url' => 'https://meet.google.com/abc',
        ]));

        $class = TeachingClass::query()->sole();
        $this->assertNull($class->location);
        $this->assertSame('https://meet.google.com/abc', $class->meeting_url);

        $this->put("/teach/classes/{$class->id}", $this->data(['type' => 'in_person', 'location' => 'مصراتة', 'meeting_url' => 'https://x.ly']));
        $class->refresh();
        $this->assertSame('مصراتة', $class->location);
        $this->assertNull($class->meeting_url);
    }

    public function test_publishing_needs_the_page_address(): void
    {
        $user = $this->instructor(slug: null);
        $class = TeachingClass::factory()->for($user->instructor)->create();

        $this->actingAs($user)->post("/teach/classes/{$class->id}/publish")
            ->assertSessionHasErrors(['class' => __('classes.needs_slug')]);
        $this->assertSame(TeachingClass::STATUS_DRAFT, $class->fresh()->status);

        $user->instructor->forceFill(['slug' => 'ahmed'])->save();
        $this->post("/teach/classes/{$class->id}/publish")->assertSessionHasNoErrors();
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $class->fresh()->status);
        $this->assertNotNull($class->fresh()->published_at);
    }

    public function test_a_class_is_published_only_with_its_image(): void
    {
        Storage::fake('public');
        $user = $this->instructor();
        $draft = TeachingClass::factory()->for($user->instructor)->create(['image_path' => null]);
        $this->actingAs($user);

        $this->post("/teach/classes/{$draft->id}/publish")
            ->assertSessionHasErrors(['class' => __('classes.image_required_to_publish')]);
        $this->assertSame(TeachingClass::STATUS_DRAFT, $draft->fresh()->status);

        // A draft may be saved without an image; once it has one, it publishes.
        $this->put("/teach/classes/{$draft->id}", $this->data(['image' => UploadedFile::fake()->image('c.png', 1200, 800)]))
            ->assertSessionHasNoErrors();
        $this->post("/teach/classes/{$draft->id}/publish")->assertSessionHasNoErrors();
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $draft->fresh()->status);

        // Its image cannot be taken away while it is shown to visitors.
        $this->put("/teach/classes/{$draft->id}", $this->data(['remove_image' => true]))
            ->assertSessionHasErrors(['image' => __('classes.image_required_published')]);
        $this->assertNotNull($draft->fresh()->image_path);
    }

    public function test_a_class_without_an_image_is_not_shown_to_visitors(): void
    {
        $user = $this->instructor();
        $shown = TeachingClass::factory()->published()->for($user->instructor)->create(['title' => 'له صورة']);
        $hidden = TeachingClass::factory()->published()->for($user->instructor)->create(['title' => 'بلا صورة', 'image_path' => null]);

        $this->get('/ahmed')->assertOk()->assertSee('له صورة')->assertDontSee('بلا صورة');
        $this->get("/ahmed/classes/{$shown->id}")->assertOk();
        $this->get("/ahmed/classes/{$hidden->id}")->assertNotFound();

        // The owner still sees it (to add the image).
        $this->actingAs($user)->get("/ahmed/classes/{$hidden->id}")->assertOk();
    }

    public function test_the_class_page_shows_the_description_the_lists_and_the_video(): void
    {
        $user = $this->instructor();
        $class = TeachingClass::factory()->published()->for($user->instructor)->create([
            'description' => "سطر أول <b>غير مغلّظ</b>\nسطر ثان",
            'learn' => ['حل المعادلات', 'التفاضل'],
            'requirements' => ['جبر أساسي'],
            'audience' => ['طلاب الثانوي'],
            'promo_video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ]);

        $this->get("/ahmed/classes/{$class->id}")->assertOk()
            ->assertSee('سطر أول &lt;b&gt;غير مغلّظ&lt;/b&gt;', false)
            ->assertDontSee('<b>غير مغلّظ</b>', false)
            ->assertSeeInOrder([__('site.content.learn'), 'حل المعادلات', 'التفاضل', __('site.content.requirements'), 'جبر أساسي', __('site.content.audience'), 'طلاب الثانوي'])
            ->assertSee('<iframe', false)
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

        // A list with nothing in it leaves its heading out; a bad video link shows no player.
        $class->forceFill(['learn' => null, 'requirements' => null, 'promo_video_url' => 'https://evil.example/x'])->save();
        $this->get("/ahmed/classes/{$class->id}")->assertOk()
            ->assertDontSee(__('site.content.learn'))
            ->assertDontSee(__('site.content.requirements'))
            ->assertSee(__('site.content.audience'))
            ->assertDontSee('<iframe', false);
    }

    public function test_the_video_link_must_be_embeddable(): void
    {
        $this->actingAs($this->instructor());

        $this->post('/teach/classes', $this->data(['promo_video_url' => 'https://evil.example/video']))
            ->assertSessionHasErrors(['promo_video_url' => __('classes.video_invalid')]);
        $this->post('/teach/classes', $this->data(['promo_video_url' => 'http://youtu.be/dQw4w9WgXcQ']))
            ->assertSessionHasErrors('promo_video_url');
        $this->assertSame(0, TeachingClass::query()->count());

        $this->post('/teach/classes', $this->data(['promo_video_url' => 'https://vimeo.com/123456789']))->assertSessionHasNoErrors();
        $this->assertSame('https://vimeo.com/123456789', TeachingClass::query()->sole()->promo_video_url);
    }

    public function test_the_list_counts_accepted_students_and_waiting_requests(): void
    {
        $user = $this->instructor();
        $class = TeachingClass::factory()->published()->for($user->instructor)->create(['seats' => 10]);
        Enrollment::factory()->count(3)->accepted()->create(['class_id' => $class->id]);
        Enrollment::factory()->count(2)->create(['class_id' => $class->id]);
        Enrollment::factory()->create(['class_id' => $class->id, 'status' => Enrollment::STATUS_REJECTED]);

        $this->actingAs($user)->get('/teach/classes')->assertInertia(fn (Assert $page) => $page
            ->where('classes.data.0.accepted_count', 3)
            ->where('classes.data.0.requested_count', 2)
            ->where('today', TulabiClock::today()));
    }

    public function test_close_reopen_cancel_and_delete(): void
    {
        $user = $this->instructor();
        $class = TeachingClass::factory()->published()->for($user->instructor)->create();
        $this->actingAs($user);

        $this->post("/teach/classes/{$class->id}/close");
        $this->assertSame(TeachingClass::STATUS_CLOSED, $class->fresh()->status);

        $this->post("/teach/classes/{$class->id}/publish");
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $class->fresh()->status);

        // A class that was public is canceled, not deleted.
        $this->delete("/teach/classes/{$class->id}")
            ->assertSessionHasErrors(['class' => __('classes.delete_only_drafts')]);

        $this->post("/teach/classes/{$class->id}/cancel", ['reason' => 'سفر المدرّس']);
        $class->refresh();
        $this->assertSame(TeachingClass::STATUS_CANCELED, $class->status);
        $this->assertSame('سفر المدرّس', $class->cancel_reason);
        $this->assertNotNull($class->canceled_at);

        // Canceled is final and read-only.
        $this->post("/teach/classes/{$class->id}/publish")->assertSessionHasErrors('class');
        $this->post("/teach/classes/{$class->id}/cancel")->assertSessionHasErrors('class');
        $this->put("/teach/classes/{$class->id}", $this->data(['title' => 'جديد']))->assertForbidden();
        $this->assertSame(TeachingClass::STATUS_CANCELED, $class->fresh()->status);

        $draft = TeachingClass::factory()->for($user->instructor)->create();
        $this->delete("/teach/classes/{$draft->id}")->assertRedirect('/teach/classes?filter=draft');
        $this->assertSoftDeleted($draft);
        $this->get("/teach/classes/{$draft->id}/edit")->assertNotFound();
    }

    public function test_an_instructor_cannot_touch_another_instructors_class(): void
    {
        $owner = $this->instructor();
        $class = TeachingClass::factory()->for($owner->instructor)->create(['title' => 'فصل المالك']);
        $published = TeachingClass::factory()->published()->for($owner->instructor)->create();

        $other = $this->instructor('other-teacher');
        $this->actingAs($other);

        $this->get("/teach/classes/{$class->id}/edit")->assertNotFound();
        $this->put("/teach/classes/{$class->id}", $this->data(['title' => 'مسروق']))->assertForbidden();
        $this->post("/teach/classes/{$class->id}/publish")->assertForbidden();
        $this->post("/teach/classes/{$published->id}/close")->assertForbidden();
        $this->post("/teach/classes/{$published->id}/cancel")->assertForbidden();
        $this->delete("/teach/classes/{$class->id}")->assertForbidden();

        $this->get('/teach/classes?filter=draft')->assertInertia(fn (Assert $page) => $page->has('classes.data', 0));
        $this->get('/teach/classes')->assertInertia(fn (Assert $page) => $page->has('classes.data', 0));
        $this->get('/teach')->assertInertia(fn (Assert $page) => $page->has('upcoming', 0)->where('counts', []));

        $this->assertSame('فصل المالك', $class->fresh()->title);
        $this->assertSame(TeachingClass::STATUS_DRAFT, $class->fresh()->status);
        $this->assertSame(TeachingClass::STATUS_PUBLISHED, $published->fresh()->status);
        $this->assertNotSoftDeleted($class);

        // A student account cannot either.
        $this->actingAs(User::factory()->create());
        $this->put("/teach/classes/{$class->id}", $this->data())->assertForbidden();
    }

    public function test_the_owner_sees_the_meeting_link_on_their_edit_page(): void
    {
        $user = $this->instructor();
        $class = TeachingClass::factory()->online()->for($user->instructor)->create();

        $this->actingAs($user)->get("/teach/classes/{$class->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teach/Classes/Form')
                ->where('item.meeting_url', 'https://meet.google.com/abc-defg-hij'));
    }

    private function instructor(?string $slug = 'ahmed'): User
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => $slug])->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(array $overrides = []): array
    {
        return [
            'title' => 'رياضيات الثالث الثانوي',
            'description' => 'مراجعة شاملة.',
            'type' => 'in_person',
            'location' => 'طرابلس، حي الأندلس',
            'map_url' => 'https://maps.app.goo.gl/abc123',
            'meeting_url' => '',
            'starts_on' => '2026-12-01',
            'ends_on' => '2027-01-31',
            'schedule_type' => 'weekly',
            'days' => [6, 1],
            'start_time' => '17:00',
            'end_time' => '19:00',
            'price' => '300',
            'seats' => '20',
            'learn' => ['الجبر'],
            'requirements' => ['قلم ودفتر'],
            'audience' => ['طلاب الثانوي'],
            ...$overrides,
        ];
    }
}
