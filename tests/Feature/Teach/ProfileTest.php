<?php

namespace Tests\Feature\Teach;

use App\Models\Instructor;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_the_about_is_sections_starting_with_who_i_am(): void
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => 'sara', 'bio' => "سطر أول.\nسطر ثان."])->save();
        $this->actingAs($user);

        // An old plain bio shows as the first section.
        $this->get('/teach/profile')->assertInertia(fn (Assert $page) => $page
            ->where('profile.sections.0.title', 'من أنا')
            ->where('profile.sections.0.body', "<p>سطر أول.<br>\nسطر ثان.</p>")
            ->where('defaultSectionTitle', 'من أنا')
            ->has('sectionSuggestions'));

        $this->put('/teach/profile', $this->data(['sections' => [
            ['title' => 'من أنا', 'body' => '<p onclick="x()">أحب التدريس <script>alert(1)</script></p>'],
            ['title' => 'مؤهلاتي', 'body' => '<ul><li>بكالوريوس فيزياء</li></ul>'],
            ['title' => 'فارغ', 'body' => '<p> </p>'],
        ]]))->assertSessionHasErrors('sections.2.body');

        $this->put('/teach/profile', $this->data(['slug' => 'sara', 'sections' => [
            ['title' => 'من أنا', 'body' => '<p onclick="x()">أحب التدريس <script>alert(1)</script></p><p>سطر ثان</p>'],
            ['title' => 'مؤهلاتي', 'body' => '<ul><li>بكالوريوس فيزياء</li></ul>'],
            ['title' => 'الخبرة العملية', 'body' => '<p>عشر سنوات</p>'],
        ]]))->assertSessionHasNoErrors();

        $instructor = $user->instructor->fresh();
        $this->assertCount(3, $instructor->bio_sections);
        $this->assertSame(['من أنا', 'مؤهلاتي', 'الخبرة العملية'], array_column($instructor->bio_sections, 'title'));
        // The tag is gone (its text stays as plain text, as everywhere SafeHtml is used).
        $this->assertStringNotContainsString('<script', $instructor->bio_sections[0]['body']);
        $this->assertStringNotContainsString('onclick', $instructor->bio_sections[0]['body']);
        // «bio» keeps the plain text of the first section.
        $this->assertSame("أحب التدريس alert(1)\nسطر ثان", $instructor->bio);
    }

    public function test_section_rules(): void
    {
        $this->actingAs(User::factory()->instructor()->create());

        $this->put('/teach/profile', $this->data(['sections' => []]))->assertSessionHasErrors('sections');
        $this->put('/teach/profile', $this->data(['sections' => [['title' => '', 'body' => 'x']]]))->assertSessionHasErrors('sections.0.title');
        $this->put('/teach/profile', $this->data(['sections' => [['title' => str_repeat('ا', 61), 'body' => 'x']]]))->assertSessionHasErrors('sections.0.title');

        $tooMany = array_fill(0, 9, ['title' => 'قسم', 'body' => '<p>نص</p>']);
        $this->put('/teach/profile', $this->data(['sections' => $tooMany]))->assertSessionHasErrors('sections');

        // The first section may be empty; the page then has no «نبذة».
        $this->put('/teach/profile', $this->data(['sections' => [['title' => 'من أنا', 'body' => '']]]))->assertSessionHasNoErrors();
    }

    public function test_the_public_page_shows_the_sections_in_order_and_hides_empty_ones(): void
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill([
            'slug' => 'sara',
            'bio' => 'أحب التدريس',
            'bio_sections' => [
                ['title' => 'من أنا', 'body' => '<p>أحب التدريس</p>'],
                ['title' => 'مؤهلاتي', 'body' => '<ul><li>بكالوريوس فيزياء</li></ul>'],
                ['title' => 'قسم فارغ', 'body' => ''],
            ],
        ])->save();

        $this->get('/sara')->assertOk()
            ->assertSeeInOrder(['من أنا', 'أحب التدريس', 'مؤهلاتي', 'بكالوريوس فيزياء'])
            ->assertDontSee('قسم فارغ');

        $user->instructor->forceFill(['bio' => null, 'bio_sections' => [['title' => 'من أنا', 'body' => '']]])->save();
        $this->get('/sara')->assertOk()->assertDontSee('id="about"', false);
    }

    public function test_only_instructors_reach_the_instructor_area(): void
    {
        $this->get('/teach')->assertRedirect('/login');

        $this->actingAs(User::factory()->create());
        $this->get('/teach')->assertForbidden();
        $this->get('/teach/profile')->assertForbidden();
        $this->put('/teach/profile', $this->data())->assertForbidden();

        $suspendedTeaching = User::factory()->instructor()->create();
        $suspendedTeaching->instructor->forceFill(['status' => Instructor::STATUS_SUSPENDED])->save();
        $this->actingAs($suspendedTeaching)->get('/teach')->assertForbidden();
    }

    public function test_the_instructor_edits_their_page(): void
    {
        $user = User::factory()->instructor()->create(['phone' => '+218912345678']);

        $this->actingAs($user)->get('/teach/profile')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Teach/Profile')
            ->where('profile.slug', null)
            ->where('profile.public_url', null));

        $this->put('/teach/profile', $this->data([
            'slug' => '  Ahmed-Math ',
            'whatsapp' => '0921234567',
            'links' => ['https://facebook.com/ahmed', '', 'https://ahmed.ly'],
            'user_id' => 999,
            'status' => Instructor::STATUS_SUSPENDED,
            'photo_path' => 'evil.php',
        ]))->assertSessionHasNoErrors();

        $instructor = $user->instructor->fresh();
        $this->assertSame('ahmed-math', $instructor->slug);
        $this->assertSame('أحمد المدرّس', $instructor->display_name);
        $this->assertSame('+218921234567', $instructor->whatsapp);
        $this->assertSame(['https://facebook.com/ahmed', 'https://ahmed.ly'], $instructor->links);
        $this->assertSame($user->id, $instructor->user_id);
        $this->assertSame(Instructor::STATUS_ACTIVE, $instructor->status);
        $this->assertNull($instructor->photo_path);

        $this->get('/ahmed-math')->assertOk();
    }

    public function test_slug_rules(): void
    {
        User::factory()->instructor()->create()->instructor->forceFill(['slug' => 'taken'])->save();
        $this->actingAs(User::factory()->instructor()->create());

        foreach (['ab', '1abc', 'with space', 'arabic-عربي', 'a--b', 'ends-', 'under_score', str_repeat('a', 41)] as $slug) {
            $this->put('/teach/profile', $this->data(['slug' => $slug]))->assertSessionHasErrors('slug');
        }

        foreach (['admin', 'login', 'teach', 'classes', 'privacy'] as $slug) {
            $this->put('/teach/profile', $this->data(['slug' => $slug]))
                ->assertSessionHasErrors(['slug' => __('instructors.slug_reserved')]);
        }

        $this->put('/teach/profile', $this->data(['slug' => 'TAKEN']))
            ->assertSessionHasErrors(['slug' => __('instructors.slug_taken')]);

        $this->put('/teach/profile', $this->data(['slug' => 'math-2']))->assertSessionHasNoErrors();
    }

    public function test_other_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->instructor()->create());

        $this->put('/teach/profile', $this->data([
            'display_name' => '',
            'whatsapp' => '0213334444',
            'links' => ['javascript:alert(1)', 'ftp://x.ly'],
        ]))->assertSessionHasErrors(['display_name', 'whatsapp', 'links.0', 'links.1']);

        $this->put('/teach/profile', $this->data([
            'links' => array_fill(0, 6, 'https://example.com'),
        ]))->assertSessionHasErrors('links');
    }

    public function test_the_slug_cannot_be_removed_while_classes_are_public(): void
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => 'ahmed'])->save();
        TeachingClass::factory()->published()->for($user->instructor)->create();

        $this->actingAs($user)->put('/teach/profile', $this->data(['slug' => '']))
            ->assertSessionHasErrors(['slug' => __('instructors.slug_needed_for_classes')]);
        $this->assertSame('ahmed', $user->instructor->fresh()->slug);
    }

    public function test_a_photo_is_saved_again_as_a_square_jpeg(): void
    {
        Storage::fake('public');
        $user = User::factory()->instructor()->create();
        $this->actingAs($user);

        $this->post('/teach/profile/photo', ['photo' => UploadedFile::fake()->image('me.png', 900, 600)])
            ->assertSessionHasNoErrors();

        $path = $user->instructor->fresh()->photo_path;
        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith('instructors/'.$user->instructor->id.'/', $path);
        $this->assertStringEndsWith('.jpg', $path);

        $info = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([512, 512, 'image/jpeg'], [$info[0], $info[1], $info['mime']]);

        // A new photo replaces the old file.
        $this->post('/teach/profile/photo', ['photo' => UploadedFile::fake()->image('me2.jpg', 300, 300)]);
        Storage::disk('public')->assertMissing($path);
        $second = $user->instructor->fresh()->photo_path;

        $this->delete('/teach/profile/photo');
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($user->instructor->fresh()->photo_path);
    }

    public function test_bad_photos_are_refused(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->instructor()->create());

        $this->post('/teach/profile/photo', ['photo' => UploadedFile::fake()->image('tiny.jpg', 50, 50)])
            ->assertSessionHasErrors('photo');
        $this->post('/teach/profile/photo', ['photo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')])
            ->assertSessionHasErrors('photo');
        $this->post('/teach/profile/photo', ['photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(array $overrides = []): array
    {
        return [
            'display_name' => 'أحمد المدرّس',
            'specialty' => 'فيزياء',
            'city' => 'بنغازي',
            'sections' => [['title' => 'من أنا', 'body' => '<p>خبرة عشر سنوات.</p>']],
            'whatsapp' => '',
            'links' => [],
            'slug' => '',
            ...$overrides,
        ];
    }
}
