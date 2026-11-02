<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountTest extends TestCase
{
    public function test_the_account_page_shows_the_profile(): void
    {
        $user = User::factory()->create(['name' => 'سالم', 'phone' => '+218912345678']);

        $this->actingAs($user)->get('/account')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Account/Show')
            ->where('profile.name', 'سالم')
            ->where('profile.phone', '091-2345678')
            ->where('instructor', null)
            ->where('application', null));
    }

    public function test_name_and_email_can_change_but_not_the_phone_or_status(): void
    {
        $user = User::factory()->create(['phone' => '+218912345678']);

        $this->actingAs($user)->put('/account', [
            'name' => 'اسم جديد',
            'email' => 'New@Example.com',
            'phone' => '0929999999',
            'status' => User::STATUS_SUSPENDED,
        ])->assertRedirect('/account');

        $user->refresh();
        $this->assertSame('اسم جديد', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('+218912345678', $user->phone);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);

        $this->put('/account', ['name' => 'اسم جديد', 'email' => ''])->assertRedirect('/account');
        $this->assertNull($user->fresh()->email);
    }

    public function test_another_accounts_email_is_refused(): void
    {
        User::factory()->withEmail('taken@example.com')->create();

        $this->actingAs(User::factory()->create())
            ->put('/account', ['name' => 'سالم', 'email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_changing_the_password_needs_the_current_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'wrong',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('current_password');

        $this->put('/account/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/account');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->get('/account')->assertOk();
    }
}
