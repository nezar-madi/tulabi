<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_a_visitor_signs_up_with_a_libyan_phone_and_is_signed_in(): void
    {
        $this->get('/register')->assertOk();

        $this->post('/register', $this->data(['phone' => '091-234-5678']))
            ->assertRedirect('/account');

        $user = User::query()->sole();
        $this->assertSame('+218912345678', $user->phone);
        $this->assertNull($user->email);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertTrue(Hash::check('secret-pass', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_one_account_per_phone_whatever_the_format(): void
    {
        User::factory()->create(['phone' => '+218912345678']);

        $this->post('/register', $this->data(['phone' => '00218912345678']))
            ->assertSessionHasErrors(['phone' => __('accounts.phone_taken')]);

        $this->assertSame(1, User::query()->count());
        $this->assertGuest();
    }

    public function test_only_libyan_mobiles_are_accepted(): void
    {
        foreach (['0213334444', '+201001234567', '12345', ''] as $phone) {
            $this->post('/register', $this->data(['phone' => $phone]))->assertSessionHasErrors('phone');
        }

        $this->assertSame(0, User::query()->count());
    }

    public function test_status_and_other_protected_fields_cannot_be_posted(): void
    {
        $this->post('/register', $this->data([
            'status' => User::STATUS_SUSPENDED,
            'email_verified_at' => now()->toDateTimeString(),
            'id' => 999,
        ]))->assertRedirect('/account');

        $user = User::query()->sole();
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertNull($user->email_verified_at);
        $this->assertNotSame(999, $user->id);
    }

    public function test_email_is_optional_but_unique(): void
    {
        User::factory()->withEmail('taken@example.com')->create();

        $this->post('/register', $this->data(['email' => 'TAKEN@example.com']))
            ->assertSessionHasErrors(['email' => __('accounts.email_taken')]);

        $this->post('/register', $this->data(['email' => ' Me@Example.com ']))->assertRedirect('/account');
        $this->assertSame('me@example.com', User::query()->latest('id')->first()->email);
    }

    public function test_password_needs_eight_characters_and_confirmation(): void
    {
        $this->post('/register', $this->data(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');
        $this->post('/register', $this->data(['password_confirmation' => 'different']))
            ->assertSessionHasErrors('password');
    }

    public function test_the_captcha_is_checked_when_keys_are_set(): void
    {
        config(['services.recaptcha.secret' => 'test-secret', 'services.recaptcha.site_key' => 'site']);

        $this->post('/register', $this->data())
            ->assertSessionHasErrors(['g-recaptcha-response' => __('accounts.captcha')]);

        $this->assertSame(0, User::query()->count());
    }

    public function test_sign_ups_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', $this->data(['phone' => '09100000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]));
            $this->post('/logout');
        }

        $this->post('/register', $this->data(['phone' => '0919999999']))->assertStatus(429);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(array $overrides = []): array
    {
        return [
            'name' => 'سالم علي',
            'phone' => '0912345678',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'email' => '',
            ...$overrides,
        ];
    }
}
