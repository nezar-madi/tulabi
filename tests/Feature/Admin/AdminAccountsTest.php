<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Instructor;
use App\Models\InstructorApplication;
use App\Models\PasswordResetLink;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAccountsTest extends TestCase
{
    public function test_admin_pages_need_an_admin_not_a_user(): void
    {
        $user = User::factory()->create();
        $application = InstructorApplication::factory()->create();

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post("/admin/applications/{$application->id}/accept")->assertRedirect('/admin/login');

        $this->actingAs($user);
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/students')->assertRedirect('/admin/login');
        $this->post("/admin/applications/{$application->id}/accept")->assertRedirect('/admin/login');
        $this->post("/admin/users/{$user->id}/reset-link")->assertRedirect('/admin/login');

        $this->assertSame(InstructorApplication::STATUS_PENDING, $application->fresh()->status);
        $this->assertSame(0, PasswordResetLink::query()->count());
    }

    public function test_an_admin_is_not_signed_in_on_the_user_side(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/account')->assertRedirect('/login');
    }

    public function test_the_dashboard_counts(): void
    {
        User::factory()->count(2)->create();
        User::factory()->instructor()->create();
        User::factory()->suspended()->create();
        InstructorApplication::factory()->create();

        $this->actingAs($this->admin(), 'admin')->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('stats.instructors', 1)
            ->where('stats.students', 4)
            ->where('stats.pending_applications', 1)
            ->where('stats.suspended', 1));
    }

    public function test_accepting_makes_the_user_an_instructor_with_a_whatsapp_welcome(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'أحمد', 'phone' => '+218941234567']);
        $application = InstructorApplication::factory()->for($user)->create([
            'specialty' => 'فيزياء',
            'city' => 'بنغازي',
            'bio' => 'نبذة',
        ]);

        $this->actingAs($admin, 'admin')->get('/admin/applications')->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Applications/Index')
            ->has('applications.data', 1)
            ->where('applications.data.0.name', 'أحمد'));

        $this->from('/admin/applications')
            ->post("/admin/applications/{$application->id}/accept")
            ->assertRedirect('/admin/applications')
            ->assertSessionHas('welcome', fn ($welcome) => str_starts_with($welcome['whatsapp'], 'https://wa.me/218941234567?text='));

        $application->refresh();
        $this->assertSame(InstructorApplication::STATUS_ACCEPTED, $application->status);
        $this->assertSame($admin->id, $application->reviewed_by);
        $this->assertNotNull($application->reviewed_at);

        $instructor = Instructor::query()->sole();
        $this->assertSame($user->id, $instructor->user_id);
        $this->assertSame('أحمد', $instructor->display_name);
        $this->assertSame('فيزياء', $instructor->specialty);
        $this->assertSame('+218941234567', $instructor->whatsapp);
        $this->assertNull($instructor->slug);
        $this->assertTrue($user->fresh()->isInstructor());

        // Twice does nothing.
        $this->post("/admin/applications/{$application->id}/accept")->assertSessionHasErrors('application');
        $this->assertSame(1, Instructor::query()->count());
    }

    public function test_rejecting_needs_a_reason_and_keeps_it(): void
    {
        $application = InstructorApplication::factory()->create();
        $this->actingAs($this->admin(), 'admin');

        $this->post("/admin/applications/{$application->id}/reject", ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->post("/admin/applications/{$application->id}/reject", ['reason' => 'التخصص غير واضح'])
            ->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame(InstructorApplication::STATUS_REJECTED, $application->status);
        $this->assertSame('التخصص غير واضح', $application->rejection_reason);
        $this->assertSame(0, Instructor::query()->count());

        $this->post("/admin/applications/{$application->id}/accept")->assertSessionHasErrors('application');
        $this->assertSame(0, Instructor::query()->count());
    }

    public function test_lists_split_instructors_and_students_and_search_by_phone(): void
    {
        User::factory()->create(['name' => 'طالب أول', 'phone' => '+218911111111']);
        User::factory()->create(['name' => 'طالب ثان', 'phone' => '+218922222222']);
        User::factory()->instructor()->create(['name' => 'مدرّس', 'phone' => '+218943333333']);

        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/students')->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->where('kind', 'students')
            ->has('users.data', 2));
        $this->get('/admin/instructors')->assertInertia(fn (Assert $page) => $page
            ->where('kind', 'instructors')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'مدرّس'));

        $this->get('/admin/students?search=0922222222')->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'طالب ثان'));
        $this->get('/admin/students?search=أول')->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.phone', '091-1111111'));
    }

    public function test_suspend_and_activate_an_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->admin(), 'admin');

        $this->post("/admin/users/{$user->id}/suspend")->assertSessionHas('status', __('accounts.status_changed.suspended'));
        $this->assertSame(User::STATUS_SUSPENDED, $user->fresh()->status);

        $this->post("/admin/users/{$user->id}/activate");
        $this->assertSame(User::STATUS_ACTIVE, $user->fresh()->status);
    }

    public function test_a_reset_link_is_made_for_an_active_account_only(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['phone' => '+218912345678']);
        $this->actingAs($admin, 'admin');

        $this->post("/admin/users/{$user->id}/reset-link")
            ->assertSessionHas('resetLink', fn ($link) => str_contains($link['url'], '/reset/')
                && str_starts_with($link['whatsapp'], 'https://wa.me/218912345678?text='));

        $link = PasswordResetLink::query()->sole();
        $this->assertSame(PasswordResetLink::ISSUER_ADMIN, $link->issued_by_type);
        $this->assertSame($admin->id, $link->issued_by_id);

        $suspended = User::factory()->suspended()->create();
        $this->post("/admin/users/{$suspended->id}/reset-link")->assertSessionHasErrors('user');
        $this->assertSame(1, PasswordResetLink::query()->count());
    }

    public function test_settings_keep_the_notification_email(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/settings', ['applications_notify_email' => 'not-an-email'])
            ->assertSessionHasErrors('applications_notify_email');

        $this->put('/admin/settings', ['applications_notify_email' => ' Admin@Tulabi.test '])->assertSessionHasNoErrors();
        $this->assertSame('admin@tulabi.test', Setting::get(Setting::APPLICATIONS_NOTIFY_EMAIL));

        $this->put('/admin/settings', ['applications_notify_email' => '']);
        $this->assertNull(Setting::get(Setting::APPLICATIONS_NOTIFY_EMAIL));
    }

    public function test_the_create_command_makes_an_admin(): void
    {
        $this->artisan('admin:create', ['email' => 'Boss@Tulabi.test', '--name' => 'المدير'])
            ->expectsQuestion('Password (at least 12 characters)', 'a-long-password')
            ->expectsQuestion('Password again', 'a-long-password')
            ->assertSuccessful();

        $admin = Admin::query()->sole();
        $this->assertSame('boss@tulabi.test', $admin->email);
        $this->assertFalse($admin->hasTwoFactorEnabled());

        $this->artisan('admin:create', ['email' => 'other@tulabi.test', '--name' => 'x'])
            ->expectsQuestion('Password (at least 12 characters)', 'short')
            ->expectsQuestion('Password again', 'short')
            ->assertFailed();
    }

    private function admin(): Admin
    {
        return Admin::factory()->withTwoFactor()->create();
    }
}
