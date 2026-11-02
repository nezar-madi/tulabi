<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetLink;
use App\Models\User;
use App\Notifications\PasswordResetLinkMail;
use App\Support\PasswordResetLinks;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_a_link_sets_a_new_password_once_and_signs_in(): void
    {
        $user = User::factory()->create(['name' => 'سالم']);
        $path = $this->pathOf(app(PasswordResetLinks::class)->issue($user, PasswordResetLink::ISSUER_ADMIN, 1));

        $this->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Auth/ResetPassword')
            ->where('valid', true)
            ->where('name', 'سالم'));

        $this->post($path, ['password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertRedirect('/account');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

        // Used up.
        $this->post('/logout');
        $this->get($path)->assertInertia(fn (Assert $page) => $page->where('valid', false));
        $this->post($path, ['password' => 'other-password', 'password_confirmation' => 'other-password']);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_a_link_expires_after_an_hour(): void
    {
        $user = User::factory()->create();
        $path = $this->pathOf(app(PasswordResetLinks::class)->issue($user, PasswordResetLink::ISSUER_ADMIN));

        $this->travel(61)->minutes();

        $this->get($path)->assertInertia(fn (Assert $page) => $page->where('valid', false));
        $this->post($path, ['password' => 'new-password', 'password_confirmation' => 'new-password']);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_a_new_link_cancels_the_older_one(): void
    {
        $user = User::factory()->create();
        $links = app(PasswordResetLinks::class);
        $old = $this->pathOf($links->issue($user, PasswordResetLink::ISSUER_ADMIN));
        $new = $this->pathOf($links->issue($user, PasswordResetLink::ISSUER_ADMIN));

        $this->get($old)->assertInertia(fn (Assert $page) => $page->where('valid', false));
        $this->get($new)->assertInertia(fn (Assert $page) => $page->where('valid', true));
        $this->assertSame(1, PasswordResetLink::query()->count());
    }

    public function test_only_the_hash_of_the_token_is_stored(): void
    {
        $url = app(PasswordResetLinks::class)->issue(User::factory()->create(), PasswordResetLink::ISSUER_ADMIN);
        $token = basename(parse_url($url, PHP_URL_PATH));

        $this->assertDatabaseMissing('password_reset_links', ['token_hash' => $token]);
        $this->assertDatabaseHas('password_reset_links', ['token_hash' => hash('sha256', $token)]);
    }

    public function test_a_suspended_accounts_link_does_not_work(): void
    {
        $user = User::factory()->create();
        $path = $this->pathOf(app(PasswordResetLinks::class)->issue($user, PasswordResetLink::ISSUER_ADMIN));
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();

        $this->get($path)->assertInertia(fn (Assert $page) => $page->where('valid', false));
    }

    public function test_forgot_password_emails_a_link_only_to_accounts_with_an_email(): void
    {
        Notification::fake();
        $withEmail = User::factory()->withEmail('a@example.com')->create(['phone' => '+218911111111']);
        $withoutEmail = User::factory()->create(['phone' => '+218922222222']);

        $this->get('/forgot-password')->assertOk();

        // Same answer for every case, so the page tells nobody who has an account.
        foreach (['0911111111', '0922222222', '0943333333'] as $phone) {
            $this->post('/forgot-password', ['phone' => $phone])
                ->assertRedirect('/forgot-password')
                ->assertSessionHas('status', __('accounts.reset.sent'));
        }

        Notification::assertSentTo($withEmail, PasswordResetLinkMail::class);
        Notification::assertNotSentTo($withoutEmail, PasswordResetLinkMail::class);
        $this->assertSame(1, PasswordResetLink::query()->count());
        $this->assertSame(PasswordResetLink::ISSUER_EMAIL, PasswordResetLink::query()->sole()->issued_by_type);
    }

    public function test_forgot_password_is_limited_per_phone(): void
    {
        Notification::fake();
        User::factory()->withEmail()->create(['phone' => '+218911111111']);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['phone' => '0911111111'])->assertRedirect();
        }

        $this->post('/forgot-password', ['phone' => '+218911111111'])->assertStatus(429);
    }

    private function pathOf(string $url): string
    {
        return (string) parse_url($url, PHP_URL_PATH);
    }
}
