<?php

namespace Tests\Feature;

use App\Models\Instructor;
use App\Models\Program;
use App\Models\TeachingClass;
use App\Models\User;
use Tests\TestCase;

class ProgramPagesTest extends TestCase
{
    private Instructor $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->instructor()->create();
        $this->instructor = $user->instructor;
        $this->instructor->forceFill(['slug' => 'sara', 'display_name' => 'سارة'])->save();
    }

    public function test_the_instructor_page_shows_published_programs_featured_first(): void
    {
        Program::factory()->for($this->instructor)->published()->create(['name' => 'ألف']);
        Program::factory()->for($this->instructor)->published()->create(['name' => 'باء', 'is_featured' => true]);
        Program::factory()->for($this->instructor)->create(['name' => 'مخفي']);
        Program::factory()->for($this->instructor)->published()->create(['name' => 'متوقف', 'is_active' => false]);

        $this->get('/sara')->assertOk()
            ->assertSeeInOrder(['باء', 'ألف'])
            ->assertDontSee('مخفي')
            ->assertDontSee('متوقف');
    }

    public function test_the_programs_section_is_left_out_when_there_are_none(): void
    {
        Program::factory()->for($this->instructor)->create(['name' => 'مخفي']);

        $this->get('/sara')->assertOk()->assertDontSee('id="programs"', false);
    }

    public function test_the_index_lists_only_public_programs(): void
    {
        Program::factory()->for($this->instructor)->published()->create(['name' => 'ظاهر']);
        Program::factory()->for($this->instructor)->create(['name' => 'مخفي']);

        $this->get('/sara/programs')->assertOk()->assertSee('ظاهر')->assertDontSee('مخفي');
    }

    public function test_a_program_page_shows_its_open_courses_only(): void
    {
        $program = Program::factory()->for($this->instructor)->published()->create(['name' => 'رياضيات']);
        $other = Program::factory()->for($this->instructor)->published()->create(['name' => 'فيزياء']);
        TeachingClass::factory()->published()->for($this->instructor)->create(['program_id' => $program->id, 'title' => 'دورة داخل البرنامج']);
        TeachingClass::factory()->for($this->instructor)->create(['program_id' => $program->id, 'title' => 'مسودة داخله']);
        TeachingClass::factory()->published()->for($this->instructor)->create(['program_id' => $other->id, 'title' => 'دورة برنامج آخر']);
        TeachingClass::factory()->published()->for($this->instructor)->create(['title' => 'دورة بلا برنامج']);

        $this->get("/sara/programs/{$program->id}")->assertOk()
            ->assertSee('دورة داخل البرنامج')
            ->assertDontSee('مسودة داخله')
            ->assertDontSee('دورة برنامج آخر')
            ->assertDontSee('دورة بلا برنامج');
    }

    public function test_a_hidden_or_stopped_program_is_not_found_for_visitors_but_the_owner_sees_it(): void
    {
        $hidden = Program::factory()->for($this->instructor)->create();
        $stopped = Program::factory()->for($this->instructor)->published()->create(['name' => 'متوقف', 'is_active' => false]);

        $this->get("/sara/programs/{$hidden->id}")->assertNotFound();
        $this->get("/sara/programs/{$stopped->id}")->assertNotFound();

        $this->actingAs($this->instructor->user)->get("/sara/programs/{$hidden->id}")
            ->assertOk()
            ->assertSee(__('site.programs.hidden_bar'));
    }

    public function test_another_instructors_program_is_not_reachable_under_this_slug(): void
    {
        $foreign = Program::factory()->for(Instructor::factory()->create(['slug' => 'other']))->published()->create();

        $this->get("/sara/programs/{$foreign->id}")->assertNotFound();
    }

    public function test_a_course_page_links_to_its_program_only_when_that_is_public(): void
    {
        $public = Program::factory()->for($this->instructor)->published()->create(['name' => 'برنامج ظاهر']);
        $hidden = Program::factory()->for($this->instructor)->create(['name' => 'برنامج مخفي']);
        $inPublic = TeachingClass::factory()->published()->for($this->instructor)->create(['program_id' => $public->id]);
        $inHidden = TeachingClass::factory()->published()->for($this->instructor)->create(['program_id' => $hidden->id]);

        $this->get("/sara/classes/{$inPublic->id}")->assertOk()
            ->assertSee('برنامج ظاهر')
            ->assertSee("/sara/programs/{$public->id}", false);
        $this->get("/sara/classes/{$inHidden->id}")->assertOk()->assertDontSee('برنامج مخفي');
    }

    public function test_program_text_is_filtered_and_the_poster_is_shown(): void
    {
        $program = Program::factory()->for($this->instructor)->published()->create([
            'description' => '<p>نص</p>',
            'image_path' => 'program-images/1/x.jpg',
        ]);

        $this->get("/sara/programs/{$program->id}")->assertOk()->assertSee('/storage/program-images/1/x.jpg', false);
    }
}
