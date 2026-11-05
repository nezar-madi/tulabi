<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\SendCodeRequest;
use App\Http\Requests\Api\VerifyCodeRequest;
use App\Models\User;
use App\Support\Accounts;
use App\Support\PhoneVerifications;
use App\Support\RegistrationTokens;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * The app's sign-up, in the website's three steps without a session: send a code to the phone,
 * check it (the app gets a registration_token, valid 30 minutes), then choose a password (the
 * app gets its sign-in token). The code itself is App\Support\PhoneVerifications, as on the website.
 */
class RegisterController extends Controller
{
    public function sendCode(SendCodeRequest $request, PhoneVerifications $verifications): JsonResponse
    {
        $wait = $verifications->send((string) $request->validated('phone'));

        if ($wait > 0) {
            throw ValidationException::withMessages(['phone' => __('accounts.register.wait', ['seconds' => $wait])]);
        }

        return response()->json([
            'message' => __('accounts.register.code_sent'),
            'resend_in' => PhoneVerifications::RESEND_SECONDS,
            'expires_in' => PhoneVerifications::CODE_MINUTES * 60,
        ]);
    }

    public function verify(VerifyCodeRequest $request, PhoneVerifications $verifications, RegistrationTokens $tokens): JsonResponse
    {
        $phone = (string) $request->validated('phone');
        $result = $verifications->check($phone, (string) $request->validated('code'));

        if ($result !== 'ok') {
            throw ValidationException::withMessages([
                'code' => $result === 'wrong' ? __('accounts.register.code_wrong') : __('accounts.register.code_expired'),
            ]);
        }

        return response()->json([
            'registration_token' => $tokens->issue($phone),
            'expires_in' => RegistrationTokens::MINUTES * 60,
        ]);
    }

    public function register(RegisterRequest $request, RegistrationTokens $tokens, Accounts $accounts): JsonResponse
    {
        $phone = $tokens->phone($request->validated('registration_token'));

        if ($phone === null) {
            throw ValidationException::withMessages(['registration_token' => __('api.registration_expired')]);
        }

        // Someone may have made an account on this number since the code was checked (or at
        // the same moment: the unique index catches that one).
        $taken = ValidationException::withMessages(['registration_token' => __('accounts.phone_taken')]);

        if (User::query()->where('phone', $phone)->exists()) {
            throw $taken;
        }

        try {
            $user = $accounts->register(['phone' => $phone, 'password' => (string) $request->validated('password')]);
        } catch (UniqueConstraintViolationException) {
            throw $taken;
        }

        return response()->json(
            AuthController::signedIn($user, AuthController::newToken($user, (string) $request->validated('device_name'))),
            201,
        );
    }
}
