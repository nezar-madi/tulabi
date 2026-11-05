<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\PasswordResetLink;
use App\Models\User;
use App\Notifications\PasswordResetLinkMail;
use App\Support\PasswordLogin;
use App\Support\PasswordResetLinks;
use App\Support\UserPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

/**
 * The app's sign-in and sign-out. A sign-in is a token per device (Sanctum); the app sends it
 * as "Authorization: Bearer ..." on every request.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request, PasswordLogin $login): JsonResponse
    {
        $user = $login->attempt(
            (string) $request->validated('phone'),
            (string) $request->validated('password'),
            (string) $request->ip(),
        );

        return response()->json(self::signedIn($user, self::newToken($user, (string) $request->validated('device_name'))));
    }

    /** Sign out this device. */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => __('api.signed_out')]);
    }

    /** Sign out every device (the app's; the website has its own sign-out). */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => __('api.signed_out_everywhere')]);
    }

    /**
     * «نسيت كلمة المرور»: as on the website, an email link for accounts with an email. The answer
     * is the same whether the phone exists or not.
     */
    public function forgotPassword(ForgotPasswordRequest $request, PasswordResetLinks $links): JsonResponse
    {
        $user = User::query()->where('phone', $request->validated('phone'))->first();

        if ($user !== null && $user->isActive() && $user->email !== null) {
            $url = $links->issue($user, PasswordResetLink::ISSUER_EMAIL);

            try {
                $user->notify(new PasswordResetLinkMail($url));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json(['message' => __('accounts.reset.sent')]);
    }

    /** A sign-in token for one device, ending after config sanctum.expiration minutes. */
    public static function newToken(User $user, string $deviceName): NewAccessToken
    {
        $minutes = config('sanctum.expiration');

        return $user->createToken($deviceName, ['*'], $minutes ? now()->addMinutes((int) $minutes) : null);
    }

    /**
     * The answer to a sign-in or a sign-up: the token (shown once) and the account.
     *
     * @return array<string, mixed>
     */
    public static function signedIn(User $user, NewAccessToken $token): array
    {
        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => UserPresenter::forApp($user),
        ];
    }
}
