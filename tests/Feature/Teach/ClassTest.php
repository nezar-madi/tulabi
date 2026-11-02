<?php

namespace Tests\Feature\Teach;

use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClassTest extends TestCase
{
    public function test_a_new_class_is_a_draft_with_a_filtered_description(): void
    {
        $user = $this->instructor();

        $this->actingAs($user)->get('/teach/classes/create')->assertOk();

        $response = $this->post('/teach/classes', $this->data([
            'description' => '<p onclick="x()">شرح <script>alert(1)</script><a href="javascript:alert(1)">رابط</a></p>',
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
        $this->assertStringNotContainsString('script', $class->description);
        $this->assertStringNotContainsString('onclick', $class->description);
        $this->assertStringNotContainsString('javascript', $class->description);
        $this->assertSame('2026-12-01', $class->starts_on->toDateString());
    }

    public function test_validation(): void
    {
        $this->actingAs($this->instructor());

        $this->post('/teach/classes', $this->data(['type' => 'in_person', 'map_url' => '']))
            ->assertSessionHasErrors('map_url');
        $this->post('/teach/classes', $this->data(['description' => '']))->assertSessionHasErrors('description');
        $this->post('/teach/classes', $this->data(['description' => '<p> </p><br>']))->assertSessionHasErrors('description');
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

        // Manual needs dates inside the period; they are sorted and de-duplicated; the end follows the last date.
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
            'ends_on' => '',
            'manual_dates' => ['2026-12-20', '2026-12-05', '2026-12-05'],
        ]))->assertSessionHasNoErrors();
        $manual = TeachingClass::query()->latest('id')->first();
        $this->assertSame(['2026-12-05', '2026-12-20'], $manual->manual_dates);
        $this->assertNull($manual->days);
        $this->assertSame('2026-12-20', $manual->ends_on->toDateString());

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
            'description' => '<p>مراجعة شاملة.</p>',
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
            ...$overrides,
        ];
    }
}
