<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Support\TwoFactor\AdminTwoFactor;
use App\Support\TwoFactor\Totp;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    /** Middle of a 30-second step, so codes don't roll over during a test. */
    private const NOW = 1_800_000_015;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::createFromTimestamp(self::NOW));
    }

    public function test_totp_matches_the_rfc_6238_sha1_test_vectors(): void
    {
        $key = '12345678901234567890';

        $vectors = [
            59 => '94287082',
            1111111109 => '07081804',
            1111111111 => '14050471',
            1234567890 => '89005924',
            2000000000 => '69279037',
            20000000000 => '65353130',
        ];

        foreach ($vectors as $time => $expected) {
            $this->assertSame($expected, Totp::codeForStep($key, Totp::stepAt($time), 8), "T={$time}");
            $this->assertSame(substr($expected, -6), Totp::codeForStep($key, Totp::stepAt($time)), "T={$time} (6 digits)");
        }

        $secret = Totp::base32Encode($key);
        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $secret);
        $this->assertSame($key, Totp::base32Decode($secret));
        $this->assertSame('287082', Totp::code($secret, 59));

        // ±1 step is accepted, further is not, and a used step can't be used again.
        $this->assertSame(1, Totp::verify($secret, '287082', null, 59));
        $this->assertSame(1, Totp::verify($secret, '287082', null, 89));
        $this->assertSame(1, Totp::verify($secret, '287082', null, 30));
        $this->assertNull(Totp::verify($secret, '287082', null, 95));
        $this->assertNull(Totp::verify($secret, '287082', 1, 59));
        $this->assertNull(Totp::verify($secret, 'abcdef', null, 59));
        $this->assertNull(Totp::verify($secret, '28708', null, 59));

        $this->assertSame(32, strlen(Totp::generateSecret()));
        $this->assertSame(20, strlen(Totp::base32Decode(Totp::generateSecret())));
    }

    public function test_password_alone_does_not_sign_in_an_account_with_two_factor(): void
    {
        $this->account();

        $this->post('/admin/login', ['email' => 'admin@tulabi.test', 'password' => 'password'])
            ->assertRedirect('/admin/two-factor');

        $this->assertGuest('admin');

        $this->get('/admin/two-factor')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/TwoFactor/Challenge'));

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }

    public function test_the_right_code_signs_in(): void
    {
        $user = $this->account();
        $this->passwordStep();

        $this->post('/admin/two-factor', ['code' => $this->currentCode($user)])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user, 'admin');
        $this->get('/admin')->assertOk();
    }

    public function test_wrong_codes_are_rejected_and_rate_limited(): void
    {
        $user = $this->account();
        $this->passwordStep();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/two-factor', ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }
        $this->assertGuest('admin');

        // Locked out: even the right code is refused now.
        $this->post('/admin/two-factor', ['code' => $this->currentCode($user)])
            ->assertSessionHasErrors('code');
        $this->assertGuest('admin');

        // After the lockout, it works again.
        $this->travel(11)->minutes();
        $this->passwordStep();
        $this->post('/admin/two-factor', ['code' => $this->currentCode($user)])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user, 'admin');
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $user = $this->account();
        $code = $this->currentCode($user);

        $this->passwordStep();
        $this->post('/admin/two-factor', ['code' => $code])
            ->assertRedirect('/admin');
        $this->post('/admin/logout');
        $this->assertGuest('admin');

        $this->passwordStep();
        $this->post('/admin/two-factor', ['code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertGuest('admin');

        // An older code (previous step) is refused as well once a newer one was used.
        $previous = Totp::code((string) $user->two_factor_secret, self::NOW - 30);
        $this->post('/admin/two-factor', ['code' => $previous])
            ->assertSessionHasErrors('code');
        $this->assertGuest('admin');
    }

    public function test_a_recovery_code_works_once(): void
    {
        $user = $this->account();
        $codes = app(AdminTwoFactor::class)->regenerateRecoveryCodes($user);
        $this->assertCount(8, $codes);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $codes[0]);

        $this->passwordStep();
        // Case and the dash don't matter.
        $this->post('/admin/two-factor', ['recovery_code' => strtolower(str_replace('-', '', $codes[0]))])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user, 'admin');
        $this->assertCount(7, $user->fresh()->recoveryCodeHashes());

        $this->post('/admin/logout');
        $this->passwordStep();
        $this->post('/admin/two-factor', ['recovery_code' => $codes[0]])
            ->assertSessionHasErrors('recovery_code');
        $this->assertGuest('admin');

        $this->post('/admin/two-factor', ['recovery_code' => $codes[1]])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user, 'admin');
    }

    public function test_an_account_without_two_factor_must_set_it_up_first(): void
    {
        $user = Admin::factory()->create(['email' => 'new@tulabi.test']);

        $this->post('/admin/login', ['email' => 'new@tulabi.test', 'password' => 'password'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user, 'admin');

        foreach (['/', '/instructors', '/applications', '/settings'] as $path) {
            $this->get(rtrim('/admin'.$path, '/'))->assertRedirect('/admin/two-factor/setup');
        }
        $this->put('/admin/settings', ['applications_notify_email' => 'x@example.com'])
            ->assertRedirect('/admin/two-factor/setup');
        $this->post('/admin/two-factor/recovery-codes', ['code' => '123456'])
            ->assertRedirect('/admin/two-factor/setup');

        $secret = null;
        $this->get('/admin/two-factor/setup')
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$secret) {
                $page->component('Admin/TwoFactor/Setup')
                    ->where('enabled', false)
                    ->where('account', 'new@tulabi.test')
                    ->where('otpauthUrl', fn ($url) => str_starts_with($url, 'otpauth://totp/'))
                    ->etc();
                $secret = $page->toArray()['props']['secret'];
            });
        $this->assertIsString($secret);

        // The secret stays the same until confirmed.
        $this->get('/admin/two-factor/setup')
            ->assertInertia(fn (Assert $page) => $page->where('secret', $secret)->etc());

        $this->post('/admin/two-factor/setup', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
        $this->get('/admin')->assertRedirect('/admin/two-factor/setup');

        $this->post('/admin/two-factor/setup', ['code' => Totp::code($secret)])
            ->assertRedirect('/admin/two-factor/setup');

        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasTwoFactorEnabled());
        $this->assertSame($secret, $fresh->two_factor_secret);
        $this->assertCount(8, $fresh->recoveryCodeHashes());
        $this->assertArrayNotHasKey('two_factor_secret', $fresh->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $fresh->toArray());

        // The recovery codes are shown once.
        $this->get('/admin/two-factor/setup')
            ->assertInertia(fn (Assert $page) => $page
                ->where('enabled', true)
                ->has('recoveryCodes', 8)
                ->etc());
        $this->get('/admin/two-factor/setup')
            ->assertInertia(fn (Assert $page) => $page
                ->where('enabled', true)
                ->where('recoveryCodes', null)
                ->where('recoveryCodesLeft', 8)
                ->etc());

        $this->get('/admin')->assertOk();
    }

    public function test_recovery_codes_can_be_regenerated_with_a_current_code(): void
    {
        $user = $this->account();
        $old = app(AdminTwoFactor::class)->regenerateRecoveryCodes($user);

        $this->actingAs($user, 'admin')
            ->post('/admin/two-factor/recovery-codes', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->post('/admin/two-factor/recovery-codes', ['code' => $this->currentCode($user)])
            ->assertRedirect('/admin/two-factor/setup');

        $this->get('/admin/two-factor/setup')
            ->assertInertia(fn (Assert $page) => $page->has('recoveryCodes', 8)->etc());

        $this->assertFalse(app(AdminTwoFactor::class)->useRecoveryCode($user->fresh(), $old[0]));
    }

    public function test_the_reset_command_clears_two_factor(): void
    {
        $user = $this->account();
        app(AdminTwoFactor::class)->regenerateRecoveryCodes($user);

        $this->artisan('admin:two-factor-reset', ['email' => 'ADMIN@tulabi.test'])
            ->expectsOutputToContain('Two-factor authentication was cleared')
            ->assertSuccessful();

        $fresh = $user->fresh();
        $this->assertFalse($fresh->hasTwoFactorEnabled());
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_recovery_codes);
        $this->assertNull($fresh->two_factor_last_step);

        // Next login goes straight to setup.
        $this->post('/admin/login', ['email' => 'admin@tulabi.test', 'password' => 'password']);
        $this->assertAuthenticatedAs($user, 'admin');
        $this->get('/admin')->assertRedirect('/admin/two-factor/setup');

        $this->artisan('admin:two-factor-reset', ['email' => 'nobody@tulabi.test'])->assertFailed();
    }

    public function test_the_challenge_expires_after_ten_minutes(): void
    {
        $user = $this->account();
        $this->passwordStep();

        $this->travel(11)->minutes();

        $this->post('/admin/two-factor', ['code' => $this->currentCode($user)])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');

        $this->get('/admin/two-factor')
            ->assertRedirect('/admin/login');
    }

    public function test_the_challenge_needs_a_password_step_first(): void
    {
        $user = $this->account();

        $this->get('/admin/two-factor')
            ->assertRedirect('/admin/login');
        $this->post('/admin/two-factor', ['code' => $this->currentCode($user)])
            ->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }

    private function account(): Admin
    {
        return Admin::factory()->withTwoFactor()->create([
            'name' => 'إدارة طلابي',
            'email' => 'admin@tulabi.test',
        ]);
    }

    private function passwordStep(): void
    {
        $this->post('/admin/login', ['email' => 'admin@tulabi.test', 'password' => 'password'])
            ->assertRedirect('/admin/two-factor');
    }

    private function currentCode(Admin $user): string
    {
        return Totp::code((string) $user->fresh()->two_factor_secret);
    }
}
