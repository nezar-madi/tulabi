<?php

namespace Tests\Feature\Api;

use App\Models\Instructor;
use App\Models\Program;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeachProgramApiTest extends TestCase
{
    public function test_an_instructor_creates_lists_and_opens_a_program(): void
    {
        Storage::fake('public');
        $instructor = $this->instructor();
        $auth = $this->bearer($instructor->user);

        $response = $this->post('/api/v1/teach/programs', [
            ...$this->data(),
            'learn' => ['نقطة أولى', ' ', 'نقطة ثانية'],
            'instructor_id' => 999,
            'image' => UploadedFile::fake()->image('poster.png', 1600, 900),
        ], $auth)
            ->assertCreated()
            ->assertJsonPath('message', __('programs.created'))
            ->assertJsonPath('program.name', 'رياضيات')
            ->assertJsonPath('program.learn', ['نقطة أولى', 'نقطة ثانية'])
            ->assertJsonPath('program.classes_count', 0);

        $program = Program::query()->sole();
        $this->assertSame($instructor->id, $program->instructor_id);
        Storage::disk('public')->assertExists($program->image_path);
        $this->assertSame(url('/storage/'.$program->image_path), $response->json('program.image_url'));

        $this->getJson('/api/v1/teach/programs', $auth)
            ->assertOk()
            ->assertJsonCount(1, 'programs')
            ->assertJsonPath('has_slug', true);
        $this->getJson("/api/v1/teach/programs/{$program->id}", $auth)->assertOk()->assertJsonPath('program.id', $program->id);
        $this->getJson('/api/v1/teach/programs/rules', $auth)->assertOk()->assertJsonPath('description.min', 20);
    }

    public function test_saving_follows_the_websites_rules(): void
    {
        $auth = $this->bearer($this->instructor()->user);

        $this->postJson('/api/v1/teach/programs', $this->data(['name' => 'ab']), $auth)->assertJsonValidationErrors('name');
        $this->postJson('/api/v1/teach/programs', $this->data(['description' => 'قصير']), $auth)->assertJsonValidationErrors('description');
        $this->postJson('/api/v1/teach/programs', $this->data(['description' => ['نص']]), $auth)->assertJsonValidationErrors('description');
        $this->postJson('/api/v1/teach/programs', $this->data(['is_published' => true]), $auth)
            ->assertJsonValidationErrors(['image' => __('programs.media_required_to_publish')]);
        $this->postJson('/api/v1/teach/programs', $this->data(['promo_video_url' => 'https://evil.example/v']), $auth)
            ->assertJsonValidationErrors('promo_video_url');

        $this->postJson('/api/v1/teach/programs', $this->data(), $auth)->assertCreated();
        $this->postJson('/api/v1/teach/programs', $this->data(['name' => 'رياضيات  ']), $auth)
            ->assertJsonValidationErrors(['name' => __('programs.name_taken')]);

        $this->assertSame(1, Program::query()->count());
    }

    public function test_saving_changes_and_a_new_image_by_post(): void
    {
        Storage::fake('public');
        $instructor = $this->instructor();
        $program = Program::factory()->for($instructor)->create(['name' => 'قديم', 'image_path' => null]);
        $auth = $this->bearer($instructor->user);

        $this->putJson("/api/v1/teach/programs/{$program->id}", $this->data(['name' => 'جديد', 'promo_video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'is_published' => true]), $auth)
            ->assertOk()
            ->assertJsonPath('program.name', 'جديد')
            ->assertJsonPath('program.is_published', true);

        // A new image means no video (one or the other).
        $this->post("/api/v1/teach/programs/{$program->id}", [...$this->data(['name' => 'جديد']), 'image' => UploadedFile::fake()->image('p.jpg', 1200, 700)], $auth)
            ->assertOk()
            ->assertJsonPath('program.promo_video_url', null);
        $this->assertNotNull($program->fresh()->image_path);

        $this->putJson("/api/v1/teach/programs/{$program->id}", $this->data(['name' => 'جديد', 'remove_image' => true]), $auth)->assertOk();
        $this->assertNull($program->fresh()->image_path);
    }

    public function test_a_program_with_classes_is_not_deleted_an_empty_one_is(): void
    {
        $instructor = $this->instructor();
        $busy = Program::factory()->for($instructor)->create(['name' => 'فيه فصول']);
        TeachingClass::factory()->for($instructor)->create(['program_id' => $busy->id]);
        $empty = Program::factory()->for($instructor)->create(['name' => 'فارغ']);
        $auth = $this->bearer($instructor->user);

        $this->deleteJson("/api/v1/teach/programs/{$busy->id}", [], $auth)
            ->assertJsonValidationErrors(['program' => __('programs.has_classes')]);
        $this->deleteJson("/api/v1/teach/programs/{$empty->id}", [], $auth)
            ->assertOk()
            ->assertJsonPath('message', __('programs.deleted'));

        $this->assertNotNull($busy->fresh());
        $this->assertNull($empty->fresh());
    }

    public function test_another_instructors_program_is_not_found(): void
    {
        $program = Program::factory()->for($this->instructor())->create(['name' => 'برنامجي']);
        $other = $this->bearer($this->instructor()->user);

        $this->getJson("/api/v1/teach/programs/{$program->id}", $other)->assertNotFound();
        $this->putJson("/api/v1/teach/programs/{$program->id}", $this->data(), $other)->assertNotFound();
        $this->deleteJson("/api/v1/teach/programs/{$program->id}", [], $other)->assertNotFound();
        $this->getJson('/api/v1/teach/programs', $other)->assertJsonCount(0, 'programs');

        $this->assertSame('برنامجي', $program->fresh()->name);
        $this->getJson('/api/v1/teach/programs', $this->bearer(User::factory()->create()))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(array $overrides = []): array
    {
        return [
            'name' => 'رياضيات',
            'description' => 'برنامج شامل لمنهج الرياضيات في الثانوية.',
            'is_active' => true,
            'is_published' => false,
            'is_featured' => false,
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
