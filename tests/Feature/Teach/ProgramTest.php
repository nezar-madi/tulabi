<?php

namespace Tests\Feature\Teach;

use App\Models\Program;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    public function test_an_instructor_makes_a_program_with_plain_text_content_and_a_poster(): void
    {
        Storage::fake('public');
        $user = $this->instructor();

        $this->actingAs($user)->get('/teach/programs/create')->assertOk();

        $response = $this->post('/teach/programs', $this->data([
            'description' => "شرح <script>alert(1)</script>\nسطر ثان",
            'learn' => ['نقطة أولى', ' ', 'نقطة ثانية'],
            'requirements' => ['متطلب'],
            'audience' => ['لمن يريد التعلم'],
            'instructor_id' => 999,
            'image' => UploadedFile::fake()->image('poster.png', 1600, 900),
        ]));

        $program = Program::query()->sole();
        $response->assertRedirect("/teach/programs/{$program->id}/edit");
        $this->assertSame($user->instructor->id, $program->instructor_id);
        $this->assertSame("شرح <script>alert(1)</script>\nسطر ثان", $program->description);
        $this->assertSame(['نقطة أولى', 'نقطة ثانية'], $program->learn);
        $this->assertSame(['متطلب'], $program->requirements);
        $this->assertSame(['لمن يريد التعلم'], $program->audience);
        $this->assertNull($program->promo_video_url);
        $this->assertStringStartsWith('program-images/'.$program->instructor_id.'/', $program->image_path);
        Storage::disk('public')->assertExists($program->image_path);
        $this->assertTrue($program->is_active);
    }

    public function test_a_program_needs_a_name_a_real_description_and_points_of_three_letters(): void
    {
        $this->actingAs($this->instructor());

        // The name: required, at least three letters.
        $this->post('/teach/programs', $this->data(['name' => 'ab']))->assertSessionHasErrors('name');
        $this->post('/teach/programs', $this->data(['name' => '  ab  ']))->assertSessionHasErrors('name');

        // The description: required, at least 20 letters (after trimming).
        $this->post('/teach/programs', $this->data(['description' => 'برنامج شامل.']))
            ->assertSessionHasErrors(['description' => __('programs.description_short', ['min' => 20])]);
        $this->post('/teach/programs', $this->data(['description' => str_repeat(' ', 30).'قصير']))->assertSessionHasErrors('description');
        $this->post('/teach/programs', $this->data(['description' => str_repeat('ا', 20)]))->assertSessionHasNoErrors();

        // The lists may stay empty, but a point written must have three letters.
        foreach (['learn', 'requirements', 'audience'] as $list) {
            $this->post('/teach/programs', $this->data(['name' => "قائمة {$list}", $list => ['مم']]))
                ->assertSessionHasErrors(["{$list}.0" => __('programs.item_short', ['min' => 3])]);
            $this->post('/teach/programs', $this->data(['name' => "قائمة ثانية {$list}", $list => ['ثلاثة']]))->assertSessionHasNoErrors();
        }
        $this->post('/teach/programs', $this->data(['name' => 'قوائم فارغة', 'learn' => [], 'requirements' => ['', '  ']]))
            ->assertSessionHasNoErrors();
    }

    public function test_the_success_dialog_opens_once_after_creating_a_program(): void
    {
        $this->actingAs($this->instructor());

        $response = $this->post('/teach/programs', $this->data());
        $program = Program::query()->sole();
        $response->assertRedirect("/teach/programs/{$program->id}/edit")->assertSessionHas('programCreated', true);

        $this->get("/teach/programs/{$program->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->where('justCreated', true));
        $this->get("/teach/programs/{$program->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->where('justCreated', false));
    }

    public function test_validation(): void
    {
        $user = $this->instructor();
        Program::factory()->for($user->instructor)->create(['name' => 'برنامج قائم']);
        $this->actingAs($user);

        $this->post('/teach/programs', $this->data(['name' => '']))->assertSessionHasErrors('name');
        $this->post('/teach/programs', $this->data(['name' => 'برنامج   قائم']))->assertSessionHasErrors(['name' => __('programs.name_taken')]);
        $this->post('/teach/programs', $this->data(['description' => '']))->assertSessionHasErrors('description');
        $this->post('/teach/programs', $this->data(['description' => " \n "]))->assertSessionHasErrors('description');
        $this->post('/teach/programs', $this->data(['promo_video_url' => 'https://evil.example/video']))->assertSessionHasErrors('promo_video_url');

        $this->assertSame(1, Program::query()->count());
    }

    public function test_a_program_has_an_image_or_a_video_never_both(): void
    {
        Storage::fake('public');
        $this->actingAs($this->instructor());
        $video = 'https://youtu.be/dQw4w9WgXcQ';

        // Both at once: refused.
        $this->post('/teach/programs', $this->data(['promo_video_url' => $video, 'image' => UploadedFile::fake()->image('p.png', 1200, 800)]))
            ->assertSessionHasErrors(['promo_video_url' => __('programs.media_either')]);
        $this->assertSame(0, Program::query()->count());

        // Neither is fine for a program that stays hidden; a video alone is fine and can be shown.
        $this->post('/teach/programs', $this->data(['name' => 'بلا وسائط']))->assertSessionHasNoErrors();
        $this->post('/teach/programs', $this->data(['name' => 'بفيديو', 'promo_video_url' => $video, 'is_published' => true]))
            ->assertSessionHasNoErrors();
        $withVideo = Program::query()->where('name', 'بفيديو')->sole();
        $this->assertSame($video, $withVideo->promo_video_url);
        $this->assertNull($withVideo->image_path);
        $this->assertTrue($withVideo->isPublic());
        $this->assertFalse(Program::query()->where('name', 'بلا وسائط')->sole()->hasMedia());

        // Swapping the video for an image of a published program works in one save.
        $this->put("/teach/programs/{$withVideo->id}", $this->data([
            'name' => 'بفيديو', 'is_published' => true, 'promo_video_url' => '', 'image' => UploadedFile::fake()->image('q.png', 1200, 800),
        ]))->assertSessionHasNoErrors();
        $this->assertNull($withVideo->fresh()->promo_video_url);
        $this->assertNotNull($withVideo->fresh()->image_path);

        // A saved image plus a video typed in is both: refused.
        $this->put("/teach/programs/{$withVideo->id}", $this->data(['name' => 'بفيديو', 'promo_video_url' => $video]))
            ->assertSessionHasErrors(['promo_video_url' => __('programs.media_either')]);
    }

    public function test_a_program_is_published_only_with_an_image_or_a_video(): void
    {
        Storage::fake('public');
        $this->actingAs($this->instructor());

        $this->post('/teach/programs', $this->data(['is_published' => true]))
            ->assertSessionHasErrors(['image' => __('programs.media_required_to_publish')]);
        $this->assertSame(0, Program::query()->count());

        $this->post('/teach/programs', $this->data(['is_published' => true, 'image' => UploadedFile::fake()->image('p.png', 1200, 800)]))
            ->assertSessionHasNoErrors();
        $program = Program::query()->sole();
        $this->assertTrue($program->is_published);

        // Taking the image away from a published program is refused; hiding it first works.
        $this->put("/teach/programs/{$program->id}", $this->data(['is_published' => true, 'remove_image' => true]))
            ->assertSessionHasErrors('image');
        $this->put("/teach/programs/{$program->id}", $this->data(['is_published' => false, 'remove_image' => true]))
            ->assertSessionHasNoErrors();
        $this->assertNull($program->fresh()->image_path);
    }

    public function test_two_instructors_may_use_the_same_program_name(): void
    {
        Program::factory()->for($this->instructor()->instructor)->create(['name' => 'رياضيات']);

        $this->actingAs($this->instructor())->post('/teach/programs', $this->data(['name' => 'رياضيات']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Program::query()->count());
    }

    public function test_editing_keeps_the_poster_unless_told_to_replace_or_remove_it(): void
    {
        Storage::fake('public');
        $this->actingAs($this->instructor());
        $this->post('/teach/programs', $this->data(['image' => UploadedFile::fake()->image('a.png', 1200, 800)]));
        $program = Program::query()->sole();
        $first = $program->image_path;

        $this->put("/teach/programs/{$program->id}", $this->data(['name' => 'اسم جديد']))->assertSessionHasNoErrors();
        $this->assertSame($first, $program->fresh()->image_path);
        $this->assertSame('اسم جديد', $program->fresh()->name);

        $this->put("/teach/programs/{$program->id}", $this->data(['image' => UploadedFile::fake()->image('b.jpg', 900, 900)]));
        $second = $program->fresh()->image_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        $this->put("/teach/programs/{$program->id}", $this->data(['remove_image' => true]));
        $this->assertNull($program->fresh()->image_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_another_instructors_program_cannot_be_opened_edited_or_deleted(): void
    {
        $other = Program::factory()->for($this->instructor()->instructor)->create();
        $this->actingAs($this->instructor());

        $this->get("/teach/programs/{$other->id}/edit")->assertNotFound();
        $this->put("/teach/programs/{$other->id}", $this->data())->assertForbidden();
        $this->delete("/teach/programs/{$other->id}")->assertNotFound();

        $this->assertSame('رياضيات', $other->fresh()->name);
    }

    public function test_only_instructors_can_use_it(): void
    {
        $this->get('/teach/programs')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/teach/programs')->assertForbidden();
    }

    public function test_the_list_shows_counts_and_orders_featured_first(): void
    {
        $user = $this->instructor();
        $a = Program::factory()->for($user->instructor)->create(['name' => 'ألف']);
        Program::factory()->for($user->instructor)->create(['name' => 'باء', 'is_featured' => true, 'is_published' => true]);
        Program::factory()->for($user->instructor)->create(['name' => 'جيم', 'is_active' => false]);
        TeachingClass::factory()->for($user->instructor)->create(['program_id' => $a->id]);

        $this->actingAs($user)->get('/teach/programs')->assertInertia(fn (Assert $page) => $page
            ->component('Teach/Programs/Index')
            ->where('counts', ['total' => 3, 'active' => 2, 'published' => 1])
            ->where('programs.0.name', 'باء')
            ->where('programs.1.name', 'ألف')
            ->where('programs.1.classes_count', 1));
    }

    public function test_a_program_with_courses_cannot_be_deleted_but_an_empty_one_can(): void
    {
        Storage::fake('public');
        $user = $this->instructor();
        $busy = Program::factory()->for($user->instructor)->create();
        TeachingClass::factory()->for($user->instructor)->create(['program_id' => $busy->id]);
        $empty = Program::factory()->for($user->instructor)->create(['name' => 'فارغ']);

        $this->actingAs($user);
        $this->delete("/teach/programs/{$busy->id}")->assertSessionHasErrors(['program' => __('programs.has_classes')]);
        $this->assertNotNull($busy->fresh());

        $this->delete("/teach/programs/{$empty->id}")->assertRedirect('/teach/programs');
        $this->assertNull(Program::query()->find($empty->id));
    }

    public function test_a_course_can_join_an_active_program_of_its_own_instructor(): void
    {
        $user = $this->instructor();
        $program = Program::factory()->for($user->instructor)->create();
        $stopped = Program::factory()->for($user->instructor)->create(['name' => 'متوقف', 'is_active' => false]);
        $foreign = Program::factory()->for($this->instructor()->instructor)->create(['name' => 'غريب']);
        $this->actingAs($user);

        $this->post('/teach/classes', $this->classData(['program_id' => $foreign->id]))->assertSessionHasErrors('program_id');
        $this->post('/teach/classes', $this->classData(['program_id' => $stopped->id]))->assertSessionHasErrors(['program_id' => __('programs.inactive')]);
        $this->post('/teach/classes', $this->classData(['program_id' => 99999]))->assertSessionHasErrors('program_id');
        $this->assertSame(0, TeachingClass::query()->count());

        $this->post('/teach/classes', $this->classData(['program_id' => $program->id]))->assertSessionHasNoErrors();
        $class = TeachingClass::query()->sole();
        $this->assertSame($program->id, $class->program_id);

        // A course already in a program that was stopped later may stay in it; moving out works.
        $program->forceFill(['is_active' => false])->save();
        $this->put("/teach/classes/{$class->id}", $this->classData(['program_id' => $program->id]))->assertSessionHasNoErrors();
        $this->put("/teach/classes/{$class->id}", $this->classData(['program_id' => '']))->assertSessionHasNoErrors();
        $this->assertNull($class->fresh()->program_id);
    }

    public function test_a_new_course_starts_with_its_programs_image_unless_told_not_to(): void
    {
        Storage::fake('public');
        $user = $this->instructor();
        $this->actingAs($user);
        $this->post('/teach/programs', $this->data(['image' => UploadedFile::fake()->image('p.png', 1600, 900)]));
        $program = Program::query()->sole();

        $this->post('/teach/classes', $this->classData(['program_id' => $program->id]))->assertSessionHasNoErrors();
        $copied = TeachingClass::query()->latest('id')->first();
        $this->assertStringStartsWith('class-images/', $copied->image_path);
        $this->assertNotSame($program->image_path, $copied->image_path);
        Storage::disk('public')->assertExists($copied->image_path);

        // An image of its own wins; asking for none skips the copy.
        $this->post('/teach/classes', $this->classData(['program_id' => $program->id, 'image' => UploadedFile::fake()->image('own.png', 900, 900)]));
        $this->post('/teach/classes', $this->classData(['program_id' => $program->id, 'no_template_image' => true]));
        $this->assertNull(TeachingClass::query()->latest('id')->first()->image_path);
        $this->assertNotNull(TeachingClass::query()->where('id', '!=', $copied->id)->orderBy('id')->first()->image_path);

        // Deleting the course's image leaves the program's alone.
        $this->put("/teach/classes/{$copied->id}", $this->classData(['program_id' => $program->id, 'remove_image' => true]));
        Storage::disk('public')->assertExists($program->fresh()->image_path);
    }

    public function test_the_new_class_form_can_arrive_with_a_program_chosen(): void
    {
        $user = $this->instructor();
        $program = Program::factory()->for($user->instructor)->create([
            'name' => 'رياضيات',
            'learn' => ['نقطة'],
            'promo_video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ]);
        $stopped = Program::factory()->for($user->instructor)->create(['name' => 'متوقف', 'is_active' => false]);
        $foreign = Program::factory()->for($this->instructor()->instructor)->create(['name' => 'غريب']);

        $this->actingAs($user);
        $this->get("/teach/classes/create?program={$program->id}")->assertInertia(fn (Assert $page) => $page
            ->where('selectedProgram', $program->id)
            ->where('programs.0.learn', ['نقطة'])
            ->where('programs.0.promo_video_url', 'https://youtu.be/dQw4w9WgXcQ'));
        $this->get("/teach/classes/create?program={$stopped->id}")->assertInertia(fn (Assert $page) => $page->where('selectedProgram', null));
        $this->get("/teach/classes/create?program={$foreign->id}")->assertInertia(fn (Assert $page) => $page->where('selectedProgram', null));
        $this->get('/teach/classes/create')->assertInertia(fn (Assert $page) => $page->where('selectedProgram', null));
    }

    public function test_the_course_form_offers_active_programs_and_the_current_one(): void
    {
        $user = $this->instructor();
        $active = Program::factory()->for($user->instructor)->create(['name' => 'نشط']);
        $stopped = Program::factory()->for($user->instructor)->create(['name' => 'متوقف', 'is_active' => false]);
        Program::factory()->for($this->instructor()->instructor)->create(['name' => 'غريب']);
        $class = TeachingClass::factory()->for($user->instructor)->create(['program_id' => $stopped->id]);

        $this->actingAs($user)->get('/teach/classes/create')->assertInertia(fn (Assert $page) => $page
            ->has('programs', 1)
            ->where('programs.0.id', $active->id)
            ->where('programs.0.name', 'نشط')
            ->where('programs.0.description', $active->description)
            ->where('programs.0.image_url', '/storage/program-images/1/test.jpg')
            ->where('programs.0.learn', []));
        $this->get("/teach/classes/{$class->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->has('programs', 2));
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function classData(array $overrides = []): array
    {
        return [
            'title' => 'فصل رياضيات',
            'description' => 'شرح.',
            'type' => 'online',
            'schedule_type' => 'daily',
            'meeting_url' => '',
            'starts_on' => '2026-12-01',
            'price' => '100',
            'ends_on' => '2027-01-31',
            'start_time' => '17:00',
            'end_time' => '19:00',
            'seats' => '0',
            'learn' => ['الجبر'],
            'requirements' => ['قلم ودفتر'],
            'audience' => ['طلاب الثانوي'],
            ...$overrides,
        ];
    }

    private function instructor(): User
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => 'teacher-'.$user->id])->save();

        return $user;
    }
}
