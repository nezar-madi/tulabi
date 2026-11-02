<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_sign_in_with_the_phone_in_any_format(): void
    {
        $user = User::factory()->create(['phone' => '+218921234567']);

        $this->get('/login')->assertOk();

        foreach (['0921234567', '+218 92 123 4567', '٠٩٢١٢٣٤٥٦٧'] as $phone) {
            $this->post('/login', ['phone' => $phone, 'password' => 'password'])->assertRedirect('/account');
            $this->assertAuthenticatedAs($user);
            $this->post('/logout')->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_a_wrong_password_or_unknown_phone_gives_the_same_answer(): void
    {
        User::factory()->create(['phone' => '+218921234567']);

        $this->post('/login', ['phone' => '0921234567', 'password' => 'wrong'])
            ->assertSessionHasErrors(['phone' => __('auth.failed')]);
        $this->post('/login', ['phone' => '0911111111', 'password' => 'password'])
            ->assertSessionHasErrors(['phone' => __('auth.failed')]);
        $this->assertGuest();
    }

    public function test_a_suspended_account_cannot_sign_in(): void
    {
        User::factory()->suspended()->create(['phone' => '+218921234567']);

        $this->post('/login', ['phone' => '0921234567', 'password' => 'password'])
            ->assertSessionHasErrors(['phone' => __('accounts.suspended')]);
        $this->assertGuest();
    }

    public function test_a_suspended_account_is_signed_out_on_its_next_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/account')->assertOk();

        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();

        $this->get('/account')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guesses_are_limited_per_phone(): void
    {
        User::factory()->create(['phone' => '+218921234567']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['phone' => '0921234567', 'password' => 'wrong']);
        }

        $this->post('/login', ['phone' => '0921234567', 'password' => 'password'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_pages_need_sign_in_and_guests_pages_send_users_on(): void
    {
        $this->get('/account')->assertRedirect('/login');

        $this->actingAs(User::factory()->create());
        $this->get('/login')->assertRedirect('/account');
        $this->get('/register')->assertRedirect('/account');
    }
}
