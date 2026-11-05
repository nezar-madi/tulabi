<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Requests\Auth\InstructorApplicationRequest;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Support\AccountProfile;
use App\Support\InstructorApplications;
use App\Support\UserPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * «حسابي» in the app: the account's details, name and email, password, and «انضم كمدرّس».
 * Same rules as the website (App\Support\AccountProfile, App\Support\InstructorApplications).
 */
class AccountController extends Controller
{
    public function __construct(private readonly AccountProfile $account) {}

    public function show(Request $request): JsonResponse
    {
        $data = $this->account->show($request->user());

        // Requests per status, always an object (an empty PHP array would be a JSON list).
        return response()->json([...$data, 'enrollmentCounts' => (object) $data['enrollmentCounts']->all()]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $this->account->update($request->user(), $request->validated());

        return response()->json([
            'message' => __('accounts.profile_saved'),
            'user' => UserPresenter::forApp($request->user()->fresh()),
        ]);
    }

    /**
     * A new password ends every sign-in, this one too; the app gets a new token for this device
     * in the answer and keeps going.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $current = $user->currentAccessToken();
        $device = $current instanceof PersonalAccessToken ? $current->name : 'app';

        $this->account->updatePassword($user, (string) $request->validated('password'));

        return response()->json([
            'message' => __('accounts.password_saved'),
            ...AuthController::signedIn($user->fresh(), AuthController::newToken($user, $device)),
        ]);
    }

    /** «انضم كمدرّس»: whether the person can apply, and what to fill in. */
    public function application(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $pending = $user->pendingInstructorApplication();
        $lastRejected = $user->instructorApplications()
            ->where('status', InstructorApplication::STATUS_REJECTED)
            ->latest('id')
            ->first();

        return response()->json([
            'can_apply' => ! $user->instructor()->exists() && $pending === null,
            'is_instructor' => $user->instructor()->exists(),
            'pending' => $pending !== null,
            'needs_name' => ! $user->hasName(),
            'previous' => $lastRejected?->only(['specialty', 'city', 'bio']),
        ]);
    }

    public function apply(InstructorApplicationRequest $request, InstructorApplications $applications): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $applications, $data) {
            $applications->submit($user, $data);

            if (isset($data['name'])) {
                $user->forceFill(['name' => trim($data['name'])])->save();
            }
        });

        return response()->json([
            'message' => __('instructors.submitted'),
            'user' => UserPresenter::forApp($user->fresh()),
        ], 201);
    }
}
