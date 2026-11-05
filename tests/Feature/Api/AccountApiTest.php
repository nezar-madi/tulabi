<?php

namespace Tests\Feature\Api;

use App\Models\InstructorApplication;
use App\Models\TeachingClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    public function test_the_account_shows_and_saves_name_and_email(): void
    {
        $user = User::factory()->create(['name' => null, 'email' => null]);
        $auth = $this->bearer($user);

        $this->getJson('/api/v1/account', $auth)
            ->assertOk()
            ->assertJsonPath('profile.name', '')
            ->assertJsonPath('instructor', null)
            ->assertJsonPath('application', null);
        $this->assertStringContainsString('"enrollmentCounts":{}', $this->getJson('/api/v1/account', $auth)->getContent());

        $this->putJson('/api/v1/account', ['name' => 'سارة علي', 'email' => 'Sara@Example.com', 'phone' => '0999999999'], $auth)
            ->assertOk()
            ->assertJsonPath('user.name', 'سارة علي');

        $user->refresh();
        $this->assertSame('سارة علي', $user->name);
        $this->assertNotSame('+218999999999', $user->phone);
        $this->putJson('/api/v1/account', ['name' => 'ab'], $auth)->assertJsonValidationErrors('name');
    }

    public function test_a_new_password_ends_other_sign_ins_and_keeps_this_one(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $otherDevice = $user->createToken('tablet')->plainTextToken;
        $auth = $this->bearer($user, 'Pixel 8');

        $this->putJson('/api/v1/account/password', ['current_password' => 'wrong', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'], $auth)
            ->assertJsonValidationErrors('current_password');

        $token = $this->putJson('/api/v1/account/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ], $auth)
            ->assertOk()
            ->assertJsonPath('message', __('accounts.password_saved'))
            ->json('token');

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));
        $this->assertSame(['Pixel 8'], $user->tokens()->pluck('name')->all());

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$otherDevice])->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$token])->assertOk();
    }

    public function test_a_student_applies_to_teach_once(): void
    {
        $user = User::factory()->create(['name' => null]);
        $auth = $this->bearer($user);

        $this->getJson('/api/v1/instructor-application', $auth)
            ->assertOk()
            ->assertJson(['can_apply' => true, 'needs_name' => true, 'pending' => false, 'previous' => null]);

        $this->postJson('/api/v1/instructor-application', ['specialty' => 'فيزياء', 'city' => 'بنغازي'], $auth)
            ->assertJsonValidationErrors('name');

        $this->postJson('/api/v1/instructor-application', ['name' => 'أحمد علي', 'specialty' => 'فيزياء', 'city' => 'بنغازي'], $auth)
            ->assertCreated()
            ->assertJsonPath('user.instructor_application_pending', true)
            ->assertJsonPath('user.name', 'أحمد علي');

        $this->assertSame(InstructorApplication::STATUS_PENDING, $user->instructorApplications()->sole()->status);
        $this->getJson('/api/v1/instructor-application', $auth)->assertJson(['can_apply' => false, 'pending' => true]);
        $this->postJson('/api/v1/instructor-application', ['specialty' => 'فيزياء', 'city' => 'بنغازي'], $auth)
            ->assertJsonValidationErrors(['specialty' => __('instructors.already_pending')]);
    }

    public function test_an_instructor_edits_their_page_and_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->instructor()->create();
        $auth = $this->bearer($user);

        $this->getJson('/api/v1/teach/profile', $auth)
            ->assertOk()
            ->assertJsonStructure(['profile' => ['display_name', 'sections', 'slug', 'photo_url'], 'sectionSuggestions', 'slugRules']);

        $this->putJson('/api/v1/teach/profile', $this->profile(['slug' => 'ahmed-physics', 'whatsapp' => '0912345678']), $auth)
            ->assertOk()
            ->assertJsonPath('message', __('instructors.profile_saved'))
            ->assertJsonPath('profile.slug', 'ahmed-physics')
            ->assertJsonPath('profile.public_url', url('/ahmed-physics'));

        $this->putJson('/api/v1/teach/profile', $this->profile(['slug' => 'admin']), $auth)->assertJsonValidationErrors('slug');

        $photoUrl = $this->post('/api/v1/teach/profile/photo', ['photo' => UploadedFile::fake()->image('me.jpg', 800, 800)], $auth)
            ->assertOk()
            ->json('profile.photo_url');
        $this->assertStringStartsWith(url('/storage/'), $photoUrl);
        $this->assertNotNull($user->instructor->fresh()->photo_path);

        $this->deleteJson('/api/v1/teach/profile/photo', [], $auth)->assertOk()->assertJsonPath('profile.photo_url', null);
    }

    public function test_the_address_stays_while_classes_are_public(): void
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => 'sara'])->save();
        TeachingClass::factory()->published()->for($user->instructor)->create();

        $this->putJson('/api/v1/teach/profile', $this->profile(['slug' => '']), $this->bearer($user))
            ->assertJsonValidationErrors(['slug' => __('instructors.slug_needed_for_classes')]);
        $this->assertSame('sara', $user->instructor->fresh()->slug);
    }

    public function test_a_student_cannot_reach_the_page_settings(): void
    {
        $this->getJson('/api/v1/teach/profile', $this->bearer(User::factory()->create()))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profile(array $overrides = []): array
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

    /** @return array<string, string> */
    private function bearer(User $user, string $device = 'test'): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken($device)->plainTextToken, 'Accept' => 'application/json'];
    }
}
