<?php

namespace Tests\Feature\Teach;

use App\Models\TeachingClass;
use App\Models\User;
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

        $this->post('/teach/classes', $this->data(['type' => 'in_person', 'location' => '']))
            ->assertSessionHasErrors('location');
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
            'meeting_url' => '',
            'starts_on' => '2026-12-01',
            'ends_on' => '2027-01-31',
            'days' => [6, 1],
            'start_time' => '17:00',
            'end_time' => '19:00',
            'price' => '300',
            'seats' => '20',
            ...$overrides,
        ];
    }
}
