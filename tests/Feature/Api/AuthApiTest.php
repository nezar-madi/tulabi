<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Support\RegistrationTokens;
use App\Support\Sms\SmsSender;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    /** @var list<array{phone: string, text: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $outbox = $this;
        $this->app->bind(SmsSender::class, fn () => new class($outbox) implements SmsSender
        {
            public function __construct(private AuthApiTest $test) {}

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

    public function test_meta_answers_without_signing_in(): void
    {
        config(['tulabi.app.min_version' => '1.2.0', 'tulabi.app.latest_version' => '1.3.0']);

        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertJson(['api_version' => 1, 'min_version' => '1.2.0', 'latest_version' => '1.3.0', 'timezone' => 'Africa/Tripoli']);

        // Even an old app gets the versions from here.
        $this->get('/api/v1/meta', ['X-App-Version' => '1.0.0'])->assertOk();
    }

    public function test_an_old_app_is_told_to_update(): void
    {
        config(['tulabi.app.min_version' => '1.2.0']);
        $user = User::factory()->create(['phone' => '+218912345678']);

        $this->postJson('/api/v1/auth/login', $this->login(), ['X-App-Version' => '1.1.9'])
            ->assertStatus(426)
            ->assertJson(['code' => 'update_required', 'min_version' => '1.2.0']);

        $this->postJson('/api/v1/auth/login', $this->login(), ['X-App-Version' => '1.2.0'])->assertOk();
        $this->postJson('/api/v1/auth/login', $this->login(), ['X-App-Version' => '2.0'])->assertOk();
        $this->assertSame(2, $user->tokens()->count());
    }

    public function test_sign_in_gives_a_token_that_opens_me(): void
    {
        $user = User::factory()->create(['phone' => '+218912345678']);

        $response = $this->postJson('/api/v1/auth/login', $this->login(['phone' => '091-234-5678']))
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'phone', 'is_instructor', 'instructor']])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.is_instructor', false)
            ->assertJsonPath('user.instructor', null);

        $this->assertSame('Pixel 8', $user->tokens()->sole()->name);
        $this->assertNotNull($user->tokens()->sole()->expires_at);

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$response->json('token')])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_me_needs_a_token_and_answers_json_even_without_asking(): void
    {
        $this->get('/api/v1/me')->assertUnauthorized()->assertJsonStructure(['message']);
        $this->get('/api/v1/me', ['Authorization' => 'Bearer 1|not-a-real-token'])->assertUnauthorized();
    }

    public function test_a_website_session_does_not_sign_in_to_the_api(): void
    {
        $this->actingAs(User::factory()->create(), 'web')->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_a_wrong_password_is_a_validation_error_and_is_limited(): void
    {
        User::factory()->create(['phone' => '+218912345678']);

        $this->postJson('/api/v1/auth/login', $this->login(['password' => 'wrong']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone' => __('auth.failed')]);

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/v1/auth/login', $this->login(['password' => 'wrong']));
        }

        // Five wrong guesses: even the right password waits now (shared with the website's limit).
        $this->postJson('/api/v1/auth/login', $this->login())->assertUnprocessable();
        $this->post('/login', ['phone' => '0912345678', 'password' => 'password'])->assertSessionHasErrors('phone');
    }

    public function test_sign_in_needs_a_device_name(): void
    {
        User::factory()->create(['phone' => '+218912345678']);

        $this->postJson('/api/v1/auth/login', $this->login(['device_name' => '']))->assertJsonValidationErrors('device_name');
    }

    public function test_a_suspended_account_cannot_sign_in_and_loses_its_tokens(): void
    {
        $user = User::factory()->create(['phone' => '+218912345678']);
        $token = $user->createToken('Pixel 8')->plainTextToken;
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();

        $this->postJson('/api/v1/auth/login', $this->login())
            ->assertJsonValidationErrors(['phone' => __('accounts.suspended')]);

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$token])
            ->assertUnauthorized()
            ->assertJson(['code' => 'account_suspended']);
        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_sign_out_ends_this_device_or_every_device(): void
    {
        $user = User::factory()->create();
        $phone = $user->createToken('phone')->plainTextToken;
        $tablet = $user->createToken('tablet')->plainTextToken;

        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$phone])->assertOk();
        $this->assertSame(['tablet'], $user->tokens()->pluck('name')->all());

        $user->createToken('laptop');
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/logout-all', [], ['Authorization' => 'Bearer '.$tablet])->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_password_reset_or_change_signs_the_app_out(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $user->createToken('phone');

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_sign_up_is_code_then_check_then_password(): void
    {
        $this->postJson('/api/v1/auth/register/code', ['phone' => '0912345678'])
            ->assertOk()
            ->assertJson(['resend_in' => 60, 'expires_in' => 600]);
        $this->assertSame('+218912345678', $this->sent[0]['phone']);

        // Asking again at once: wait.
        $this->postJson('/api/v1/auth/register/code', ['phone' => '0912345678'])->assertJsonValidationErrors('phone');

        $this->postJson('/api/v1/auth/register/verify', ['phone' => '0912345678', 'code' => '000000'])
            ->assertJsonValidationErrors(['code' => __('accounts.register.code_wrong')]);

        $registration = $this->postJson('/api/v1/auth/register/verify', ['phone' => '+218912345678', 'code' => $this->lastCode()])
            ->assertOk()
            ->assertJson(['expires_in' => 1800])
            ->json('registration_token');

        $this->assertSame(0, User::query()->count());

        $response = $this->postJson('/api/v1/auth/register', [
            'registration_token' => $registration,
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'device_name' => 'iPhone',
        ])->assertCreated()->assertJsonPath('user.phone', '091-2345678');

        $user = User::query()->sole();
        $this->assertSame('+218912345678', $user->phone);
        $this->assertTrue(Hash::check('secret-pass', $user->password));
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$response->json('token')])->assertOk();

        // The same token cannot make a second account on the number.
        $this->postJson('/api/v1/auth/register', [
            'registration_token' => $registration,
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'device_name' => 'iPhone',
        ])->assertJsonValidationErrors(['registration_token' => __('accounts.phone_taken')]);
        $this->assertSame(1, User::query()->count());
    }

    public function test_a_registration_token_must_be_ours_and_recent(): void
    {
        $data = ['password' => 'secret-pass', 'password_confirmation' => 'secret-pass', 'device_name' => 'iPhone'];

        $this->postJson('/api/v1/auth/register', ['registration_token' => 'made-up', ...$data])
            ->assertJsonValidationErrors(['registration_token' => __('api.registration_expired')]);

        $token = app(RegistrationTokens::class)->issue('+218912345678');
        $this->travel(31)->minutes();

        $this->postJson('/api/v1/auth/register', ['registration_token' => $token, ...$data])
            ->assertJsonValidationErrors('registration_token');
        $this->assertSame(0, User::query()->count());
    }

    public function test_a_number_with_an_account_gets_no_code(): void
    {
        User::factory()->create(['phone' => '+218912345678']);

        $this->postJson('/api/v1/auth/register/code', ['phone' => '0912345678'])
            ->assertJsonValidationErrors(['phone' => __('accounts.phone_taken')]);
        $this->assertSame([], $this->sent);
    }

    public function test_forgot_password_says_the_same_for_any_phone(): void
    {
        User::factory()->create(['phone' => '+218912345678', 'email' => 'a@example.com']);

        $known = $this->postJson('/api/v1/auth/forgot-password', ['phone' => '0912345678'])->assertOk()->json('message');
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['phone' => '0923456789'])->assertOk()->json('message');

        $this->assertSame($known, $unknown);
    }

    public function test_an_instructor_sees_their_side_in_me(): void
    {
        $user = User::factory()->instructor()->create();
        $user->instructor->forceFill(['slug' => 'sara'])->save();
        $token = $user->createToken('phone')->plainTextToken;

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('user.is_instructor', true)
            ->assertJsonPath('user.instructor.slug', 'sara')
            ->assertJsonPath('user.instructor.pending_requests', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function login(array $overrides = []): array
    {
        return ['phone' => '0912345678', 'password' => 'password', 'device_name' => 'Pixel 8', ...$overrides];
    }

    private function lastCode(): string
    {
        $this->assertNotEmpty($this->sent);
        preg_match('/\d{6}/', end($this->sent)['text'], $match);

        return $match[0];
    }
}
