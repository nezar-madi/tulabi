<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Program;
use App\Models\TeachingClass;
use App\Models\User;
use App\Support\Payments;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeachClassApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A fixed "today" (Libya), so the class dates below stay in the future.
        $this->travelTo('2026-11-03 10:00:00');
    }

    public function test_an_instructor_creates_a_draft_and_opens_it(): void
    {
        Storage::fake('public');
        $instructor = $this->instructor();
        $auth = $this->bearer($instructor->user);

        $this->getJson('/api/v1/teach/classes/form', $auth)
            ->assertOk()
            ->assertJsonPath('item', null)
            ->assertJsonPath('currency', 'LYD')
            ->assertJsonPath('today', '2026-11-03');

        $response = $this->post('/api/v1/teach/classes', [
            ...$this->data(),
            'status' => 'published',
            'instructor_id' => 999,
            'image' => UploadedFile::fake()->image('poster.jpg', 1600, 900),
        ], $auth)
            ->assertCreated()
            ->assertJsonPath('message', __('classes.created'))
            ->assertJsonPath('item.title', 'رياضيات الثالث الثانوي')
            ->assertJsonPath('item.status', TeachingClass::STATUS_DRAFT)
            ->assertJsonPath('canDelete', true);

        $class = TeachingClass::query()->sole();
        $this->assertSame($instructor->id, $class->instructor_id);
        $this->assertSame(url('/storage/'.$class->image_path), $response->json('item.image_url'));

        $this->getJson("/api/v1/teach/classes/{$class->id}", $auth)->assertOk()->assertJsonPath('item.id', $class->id);
        $this->getJson('/api/v1/teach/classes?filter=draft', $auth)
            ->assertOk()
            ->assertJsonPath('classes.total', 1)
            ->assertJsonPath('draftCount', 1);
        $this->assertStringContainsString('"narrow":{}', $this->getJson('/api/v1/teach/classes', $auth)->getContent());
    }

    public function test_saving_follows_the_websites_rules(): void
    {
        $auth = $this->bearer($this->instructor()->user);

        $this->postJson('/api/v1/teach/classes', $this->data(['title' => 'ab']), $auth)->assertJsonValidationErrors('title');
        $this->postJson('/api/v1/teach/classes', $this->data(['map_url' => 'https://evil.example/map']), $auth)->assertJsonValidationErrors('map_url');
        $this->postJson('/api/v1/teach/classes', $this->data(['ends_on' => '2026-12-01']), $auth)->assertJsonValidationErrors('ends_on');
        $this->postJson('/api/v1/teach/classes', $this->data(['schedule_type' => 'weekly', 'days' => []]), $auth)->assertJsonValidationErrors('days');
        $this->postJson('/api/v1/teach/classes', $this->data(['learn' => []]), $auth)->assertJsonValidationErrors('learn');
        $this->postJson('/api/v1/teach/classes', $this->data(['description' => ['نص']]), $auth)->assertJsonValidationErrors('description');

        $this->assertSame(0, TeachingClass::query()->count());
    }

    public function test_saving_tells_when_the_meeting_link_or_the_schedule_changed(): void
    {
        $instructor = $this->instructor();
        $auth = $this->bearer($instructor->user);
        $id = $this->postJson('/api/v1/teach/classes', $this->data(['type' => 'online', 'map_url' => '', 'promo_video_url' => 'https://youtu.be/dQw4w9WgXcQ']), $auth)
            ->assertCreated()
            ->json('item.id');
        $class = TeachingClass::query()->findOrFail($id);
        Enrollment::factory()->accepted()->for($class, 'teachingClass')->create();

        $this->putJson("/api/v1/teach/classes/{$id}", $this->data([
            'type' => 'online', 'map_url' => '', 'promo_video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'meeting_url' => 'https://meet.example/new', 'start_time' => '18:00', 'end_time' => '20:00',
        ]), $auth)
            ->assertOk()
            ->assertJsonPath('message', __('classes.saved'))
            ->assertJsonPath('meeting_changed', true)
            ->assertJsonPath('schedule_changed', true)
            ->assertJsonPath('item.start_time', '18:00');

        $this->assertNotNull($class->fresh()->schedule_changed_at);
        $this->assertNotNull($this->putJson("/api/v1/teach/classes/{$id}", $this->data([
            'type' => 'online', 'map_url' => '', 'promo_video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'meeting_url' => 'https://meet.example/new', 'start_time' => '18:00', 'end_time' => '20:00',
        ]), $auth)->assertJsonPath('meeting_changed', false)->json('item'));
    }

    public function test_a_class_is_published_closed_canceled_and_deleted(): void
    {
        $instructor = $this->instructor();
        $class = TeachingClass::factory()->for($instructor)->create(['promo_video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $auth = $this->bearer($instructor->user);

        $this->postJson("/api/v1/teach/classes/{$class->id}/publish", [], $auth)
            ->assertOk()
            ->assertJsonPath('message', __('classes.published'))
            ->assertJsonPath('item.status', TeachingClass::STATUS_PUBLISHED)
            ->assertJsonPath('canDelete', false);

        $this->postJson("/api/v1/teach/classes/{$class->id}/close", [], $auth)
            ->assertOk()
            ->assertJsonPath('item.status', TeachingClass::STATUS_CLOSED);

        $this->postJson("/api/v1/teach/classes/{$class->id}/cancel", ['reason' => 'ظرف طارئ'], $auth)
            ->assertOk()
            ->assertJsonPath('item.status', TeachingClass::STATUS_CANCELED)
            ->assertJsonPath('canDelete', true);

        $this->deleteJson("/api/v1/teach/classes/{$class->id}", [], $auth)
            ->assertOk()
            ->assertJsonPath('message', __('classes.deleted_class'));
        $this->assertNull(TeachingClass::query()->find($class->id));
    }

    public function test_a_class_with_payments_is_not_deleted(): void
    {
        $instructor = $this->instructor();
        $class = TeachingClass::factory()->published()->for($instructor)->create(['price' => '300']);
        $enrollment = Enrollment::factory()->accepted('300.000')->for($class, 'teachingClass')->create();
        app(Payments::class)->record($enrollment, ['amount' => '100', 'paid_at' => '2026-11-03', 'method' => 'cash'], $instructor->user);
        $auth = $this->bearer($instructor->user);

        $this->postJson("/api/v1/teach/classes/{$class->id}/cancel", ['reason' => 'ظرف'], $auth)->assertOk();
        $this->deleteJson("/api/v1/teach/classes/{$class->id}", [], $auth)->assertUnprocessable();
        $this->assertNotNull(TeachingClass::query()->find($class->id));
    }

    public function test_a_new_class_can_start_from_a_program(): void
    {
        $instructor = $this->instructor();
        $program = Program::factory()->for($instructor)->create(['name' => 'رياضيات']);
        $auth = $this->bearer($instructor->user);

        $this->getJson("/api/v1/teach/classes/form?program={$program->id}", $auth)->assertJsonPath('selectedProgram', $program->id);

        $this->postJson('/api/v1/teach/classes', $this->data(['from_program' => true, 'program_id' => $program->id]), $auth)
            ->assertCreated()
            ->assertJsonPath('item.program_id', $program->id);
    }

    public function test_another_instructors_class_is_not_found(): void
    {
        $class = TeachingClass::factory()->for($this->instructor())->create(['title' => 'فصلي']);
        $other = $this->bearer($this->instructor()->user);

        $this->getJson("/api/v1/teach/classes/{$class->id}", $other)->assertNotFound();
        $this->putJson("/api/v1/teach/classes/{$class->id}", $this->data(), $other)->assertForbidden();
        $this->postJson("/api/v1/teach/classes/{$class->id}/publish", [], $other)->assertForbidden();
        $this->postJson("/api/v1/teach/classes/{$class->id}/cancel", ['reason' => 'x'], $other)->assertForbidden();
        $this->deleteJson("/api/v1/teach/classes/{$class->id}", [], $other)->assertForbidden();
        $this->getJson('/api/v1/teach/classes?filter=draft', $other)->assertJsonPath('classes.total', 0);

        $this->assertSame('فصلي', $class->fresh()->title);
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
            'starts_on' => '2026-12-05',
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

    /** @return array<string, string> */
    private function bearer(User $user): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken, 'Accept' => 'application/json'];
    }

    private function instructor(): Instructor
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => 'teacher-'.$user->id])->save();

        return $user->instructor;
    }
}
