<?php

namespace Tests\Feature;

use App\Models\InstructorApplication;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\InstructorApplicationSubmitted;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InstructorApplicationTest extends TestCase
{
    public function test_a_visitor_makes_an_account_and_applies_in_one_form(): void
    {
        $this->get('/join')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Join/Create')
            ->where('signedIn', false));

        $this->post('/join', [
            'name' => 'أحمد المدرّس',
            'phone' => '0941234567',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'specialty' => 'فيزياء',
            'city' => 'بنغازي',
            'bio' => 'خبرة عشر سنوات.',
        ])->assertRedirect('/account');

        $user = User::query()->sole();
        $this->assertSame('+218941234567', $user->phone);
        $this->assertAuthenticatedAs($user);

        $application = InstructorApplication::query()->sole();
        $this->assertSame($user->id, $application->user_id);
        $this->assertSame(InstructorApplication::STATUS_PENDING, $application->status);
        $this->assertSame('فيزياء', $application->specialty);
        $this->assertFalse($user->isInstructor());

        $this->get('/account')->assertInertia(fn (Assert $page) => $page
            ->where('application.status', InstructorApplication::STATUS_PENDING));
    }

    public function test_a_signed_in_student_applies_with_the_teaching_details_only(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/join')->assertInertia(fn (Assert $page) => $page->where('signedIn', true));

        $this->post('/join', ['specialty' => 'رياضيات', 'city' => 'طرابلس'])->assertRedirect('/account');

        $this->assertSame($user->id, InstructorApplication::query()->sole()->user_id);
        $this->assertSame(1, User::query()->count());
    }

    public function test_a_visitor_cannot_apply_with_a_phone_that_has_an_account(): void
    {
        User::factory()->create(['phone' => '+218941234567']);

        $this->post('/join', [
            'name' => 'أحمد',
            'phone' => '0941234567',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'specialty' => 'فيزياء',
            'city' => 'بنغازي',
        ])->assertSessionHasErrors(['phone' => __('accounts.phone_taken')]);

        $this->assertSame(0, InstructorApplication::query()->count());
    }

    public function test_one_pending_application_at_a_time_and_none_for_instructors(): void
    {
        $user = User::factory()->create();
        InstructorApplication::factory()->for($user)->create();

        $this->actingAs($user)->get('/join')->assertRedirect('/account');
        $this->post('/join', ['specialty' => 'رياضيات', 'city' => 'طرابلس'])
            ->assertSessionHasErrors(['specialty' => __('instructors.already_pending')]);

        $instructor = User::factory()->instructor()->create();
        $this->actingAs($instructor)->get('/join')->assertRedirect('/account');
        $this->post('/join', ['specialty' => 'رياضيات', 'city' => 'طرابلس'])
            ->assertSessionHasErrors(['specialty' => __('instructors.already_instructor')]);

        $this->assertSame(1, InstructorApplication::query()->count());
    }

    public function test_after_a_rejection_the_user_may_apply_again(): void
    {
        $user = User::factory()->create();
        $rejected = InstructorApplication::factory()->for($user)->create(['specialty' => 'كيمياء']);
        $rejected->forceFill(['status' => InstructorApplication::STATUS_REJECTED, 'rejection_reason' => 'نقص في البيانات'])->save();

        $this->actingAs($user)->get('/account')->assertInertia(fn (Assert $page) => $page
            ->where('application.status', InstructorApplication::STATUS_REJECTED)
            ->where('application.rejection_reason', 'نقص في البيانات'));

        $this->get('/join')->assertOk()->assertInertia(fn (Assert $page) => $page->where('previous.specialty', 'كيمياء'));
        $this->post('/join', ['specialty' => 'كيمياء', 'city' => 'مصراتة'])->assertRedirect('/account');

        $this->assertSame(2, InstructorApplication::query()->count());
    }

    public function test_status_cannot_be_posted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/join', [
            'specialty' => 'رياضيات',
            'city' => 'طرابلس',
            'status' => InstructorApplication::STATUS_ACCEPTED,
            'user_id' => 999,
        ]);

        $application = InstructorApplication::query()->sole();
        $this->assertSame(InstructorApplication::STATUS_PENDING, $application->status);
        $this->assertSame($user->id, $application->user_id);
    }

    public function test_the_admin_is_emailed_when_a_notification_address_is_set(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->create())
            ->post('/join', ['specialty' => 'رياضيات', 'city' => 'طرابلس']);
        Notification::assertNothingSent();

        Setting::put(Setting::APPLICATIONS_NOTIFY_EMAIL, 'admin@tulabi.test');
        $this->actingAs(User::factory()->create())
            ->post('/join', ['specialty' => 'رياضيات', 'city' => 'طرابلس']);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            InstructorApplicationSubmitted::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'admin@tulabi.test',
        );
    }
}
