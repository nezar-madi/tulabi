<?php

namespace Tests\Feature\Auth;

use App\Models\PhoneVerification;
use App\Models\User;
use App\Support\PhoneVerifications;
use App\Support\Sms\SmsSender;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    /** @var list<array{phone: string, text: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $outbox = $this;
        $this->app->bind(SmsSender::class, fn () => new class($outbox) implements SmsSender
        {
            public function __construct(private RegistrationTest $test) {}

            public function send(string $phone, string $text): void
            {
                $this->test->record($phone, $text);
            }
        });
    }

    public function record(string $phone, string $text): void
    {
        $this->sent[] = ['phone' => $phone, 'text' => $text];
    }

    public function test_sign_up_is_the_phone_then_the_code_then_a_password(): void
    {
        $this->get('/register')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Register')
            ->where('step', 'phone'));

        $this->post('/register', ['phone' => '091-234-5678'])->assertRedirect('/register');
        $this->assertCount(1, $this->sent);
        $this->assertSame('+218912345678', $this->sent[0]['phone']);

        $this->get('/register')->assertInertia(fn (Assert $page) => $page
            ->where('step', 'code')
            ->where('phone', '091-2345678'));

        $this->post('/register/verify', ['code' => $this->lastCode()])->assertRedirect('/register');
        $this->assertSame(0, User::query()->count());

        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('step', 'password'));

        $this->post('/register/password', ['password' => 'secret-pass', 'password_confirmation' => 'secret-pass'])
            ->assertRedirect('/account');

        $user = User::query()->sole();
        $this->assertSame('+218912345678', $user->phone);
        $this->assertFalse($user->hasName());
        $this->assertNull($user->email);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertTrue(Hash::check('secret-pass', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, PhoneVerification::query()->count());
    }

    public function test_an_account_without_a_name_shows_its_phone_until_the_profile_is_filled(): void
    {
        $user = User::query()->forceCreate([
            'phone' => '+218912345678',
            'password' => 'secret-pass',
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertSame('091-2345678', $user->fresh()->name);
        $this->assertFalse($user->fresh()->hasName());

        $this->actingAs($user)->get('/account')->assertInertia(fn (Assert $page) => $page->where('profile.name', ''));

        $this->put('/account', ['name' => 'سالم علي', 'email' => ''])->assertRedirect('/account');

        $this->assertTrue($user->fresh()->hasName());
        $this->assertSame('سالم علي', $user->fresh()->name);
    }

    public function test_the_password_step_needs_a_verified_phone(): void
    {
        $this->post('/register/password', ['password' => 'secret-pass', 'password_confirmation' => 'secret-pass'])
            ->assertRedirect('/register');

        $this->post('/register', ['phone' => '0912345678']);
        $this->post('/register/password', ['password' => 'secret-pass', 'password_confirmation' => 'secret-pass'])
            ->assertRedirect('/register');

        $this->assertSame(0, User::query()->count());
        $this->assertGuest();
    }

    public function test_a_wrong_code_is_refused_and_five_wrong_tries_void_it(): void
    {
        $this->post('/register', ['phone' => '0912345678']);
        $good = $this->lastCode();
        $wrong = $good === '000000' ? '111111' : '000000';

        $this->post('/register/verify', ['code' => $wrong])
            ->assertSessionHasErrors(['code' => __('accounts.register.code_wrong')]);

        for ($i = 0; $i < PhoneVerifications::MAX_ATTEMPTS - 2; $i++) {
            $this->post('/register/verify', ['code' => $wrong]);
        }

        $this->post('/register/verify', ['code' => $wrong])
            ->assertSessionHasErrors(['code' => __('accounts.register.code_expired')]);

        // The right code no longer works once the tries ran out.
        $this->post('/register/verify', ['code' => $good])
            ->assertSessionHasErrors(['code' => __('accounts.register.code_expired')]);

        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('step', 'code'));
        $this->post('/register/password', ['password' => 'secret-pass', 'password_confirmation' => 'secret-pass'])
            ->assertRedirect('/register');
        $this->assertSame(0, User::query()->count());
    }

    public function test_an_expired_code_is_refused(): void
    {
        $this->post('/register', ['phone' => '0912345678']);
        $code = $this->lastCode();

        $this->travel(PhoneVerifications::CODE_MINUTES + 1)->minutes();

        $this->post('/register/verify', ['code' => $code])
            ->assertSessionHasErrors(['code' => __('accounts.register.code_expired')]);

        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('step', 'phone'));
    }

    public function test_a_code_is_only_resent_after_the_wait(): void
    {
        $this->post('/register', ['phone' => '0912345678']);
        $first = $this->lastCode();

        $this->post('/register/resend')->assertSessionHasErrors('code');
        $this->assertCount(1, $this->sent);

        $this->travel(PhoneVerifications::RESEND_SECONDS + 1)->seconds();
        $this->post('/register/resend')->assertRedirect('/register');
        $this->assertCount(2, $this->sent);

        // The old code stops working when a new one is sent.
        $second = $this->lastCode();
        if ($second !== $first) {
            $this->post('/register/verify', ['code' => $first])->assertSessionHasErrors('code');
        }
        $this->post('/register/verify', ['code' => $second])->assertRedirect('/register');
        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('step', 'password'));
    }

    public function test_arabic_digits_in_the_code_are_accepted(): void
    {
        $this->post('/register', ['phone' => '0912345678']);
        $code = strtr($this->lastCode(), ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);

        $this->post('/register/verify', ['code' => $code]);

        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('step', 'password'));
    }

    public function test_change_phone_goes_back_to_the_first_step(): void
    {
        $this->post('/register', ['phone' => '0912345678']);

        $this->post('/register/restart')->assertRedirect('/register');

        $this->get('/register')->assertInertia(fn (Assert $page) => $page->where('step', 'phone'));
    }

    public function test_one_account_per_phone_whatever_the_format(): void
    {
        User::factory()->create(['phone' => '+218912345678']);

        $this->post('/register', ['phone' => '00218912345678'])
            ->assertSessionHasErrors(['phone' => __('accounts.phone_taken')]);

        $this->assertCount(0, $this->sent);
        $this->assertSame(1, User::query()->count());
        $this->assertGuest();
    }

    public function test_a_number_taken_after_the_code_was_checked_cannot_be_registered(): void
    {
        $this->post('/register', ['phone' => '0912345678']);
        $this->post('/register/verify', ['code' => $this->lastCode()]);

        User::factory()->create(['phone' => '+218912345678']);

        $this->post('/register/password', ['password' => 'secret-pass', 'password_confirmation' => 'secret-pass'])
            ->assertSessionHasErrors('password');
        $this->assertSame(1, User::query()->count());
        $this->assertGuest();
    }

    public function test_only_libyan_mobiles_are_accepted(): void
    {
        foreach (['0213334444', '+201001234567', '12345', ''] as $phone) {
            $this->post('/register', ['phone' => $phone])->assertSessionHasErrors('phone');
        }

        $this->assertCount(0, $this->sent);
    }

    public function test_the_password_needs_eight_characters_and_confirmation(): void
    {
        $this->verifiedPhone();

        $this->post('/register/password', ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->post('/register/password', ['password' => 'secret-pass', 'password_confirmation' => 'different'])
            ->assertSessionHasErrors('password');

        $this->assertSame(0, User::query()->count());
    }

    public function test_other_fields_cannot_be_posted_with_the_password(): void
    {
        $this->verifiedPhone();

        $this->post('/register/password', [
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'phone' => '0999999999',
            'status' => User::STATUS_SUSPENDED,
            'email_verified_at' => now()->toDateTimeString(),
            'id' => 999,
        ])->assertRedirect('/account');

        $user = User::query()->sole();
        $this->assertSame('+218912345678', $user->phone);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertNull($user->email_verified_at);
        $this->assertNotSame(999, $user->id);
    }

    public function test_a_verified_phone_is_forgotten_after_half_an_hour(): void
    {
        $this->verifiedPhone();

        $this->travel(31)->minutes();

        $this->post('/register/password', ['password' => 'secret-pass', 'password_confirmation' => 'secret-pass'])
            ->assertRedirect('/register');
        $this->assertSame(0, User::query()->count());
    }

    public function test_the_captcha_is_checked_before_a_code_is_sent(): void
    {
        config(['services.recaptcha.secret' => 'test-secret', 'services.recaptcha.site_key' => 'site']);

        $this->post('/register', ['phone' => '0912345678'])
            ->assertSessionHasErrors(['g-recaptcha-response' => __('accounts.captcha')]);

        $this->assertCount(0, $this->sent);
    }

    public function test_codes_are_limited_per_phone(): void
    {
        // Five codes for one number in an hour (a wait between each), then no more.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', ['phone' => '0912345678'])->assertRedirect('/register');
            $this->travel(PhoneVerifications::RESEND_SECONDS + 1)->seconds();
        }

        $this->post('/register', ['phone' => '0912345678'])->assertStatus(429);
    }

    public function test_codes_are_limited_per_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', ['phone' => '09100000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $this->post('/register', ['phone' => '0919999999'])->assertStatus(429);
    }

    public function test_a_signed_in_user_is_sent_away_from_sign_up(): void
    {
        $this->actingAs(User::factory()->create())->get('/register')->assertRedirect('/account');
        $this->post('/register', ['phone' => '0912345678'])->assertRedirect('/account');
        $this->assertCount(0, $this->sent);
    }

    private function lastCode(): string
    {
        $this->assertNotEmpty($this->sent);
        preg_match('/\d{6}/', end($this->sent)['text'], $match);

        return $match[0];
    }

    private function verifiedPhone(): void
    {
        $this->post('/register', ['phone' => '0912345678']);
        $this->post('/register/verify', ['code' => $this->lastCode()]);
    }
}
