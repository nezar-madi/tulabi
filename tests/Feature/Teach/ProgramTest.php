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
    public function test_an_instructor_makes_a_program_with_a_filtered_description_and_a_poster(): void
    {
        Storage::fake('public');
        $user = $this->instructor();

        $this->actingAs($user)->get('/teach/programs/create')->assertOk();

        $response = $this->post('/teach/programs', $this->data([
            'description' => '<p onclick="x()">شرح <script>alert(1)</script></p>',
            'instructor_id' => 999,
            'image' => UploadedFile::fake()->image('poster.png', 1600, 900),
        ]));

        $program = Program::query()->sole();
        $response->assertRedirect("/teach/programs/{$program->id}/edit");
        $this->assertSame($user->instructor->id, $program->instructor_id);
        $this->assertStringNotContainsString('script', $program->description);
        $this->assertStringNotContainsString('onclick', $program->description);
        $this->assertStringStartsWith('program-images/'.$program->instructor_id.'/', $program->image_path);
        Storage::disk('public')->assertExists($program->image_path);
        $this->assertTrue($program->is_active);
    }

    public function test_validation(): void
    {
        $user = $this->instructor();
        Program::factory()->for($user->instructor)->create(['name' => 'برنامج قائم']);
        $this->actingAs($user);

        $this->post('/teach/programs', $this->data(['name' => '']))->assertSessionHasErrors('name');
        $this->post('/teach/programs', $this->data(['name' => 'برنامج   قائم']))->assertSessionHasErrors(['name' => __('programs.name_taken')]);
        $this->post('/teach/programs', $this->data(['description' => '']))->assertSessionHasErrors('description');
        $this->post('/teach/programs', $this->data(['description' => '<p> </p><br>']))->assertSessionHasErrors('description');

        $this->assertSame(1, Program::query()->count());
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
            ->where('programs.0.image_url', null));
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
            'description' => '<p>برنامج شامل.</p>',
            'is_active' => true,
            'is_published' => true,
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
            'title' => 'دورة رياضيات',
            'description' => '<p>شرح.</p>',
            'type' => 'online',
            'schedule_type' => 'daily',
            'meeting_url' => '',
            'starts_on' => '2026-12-01',
            'price' => '100',
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
