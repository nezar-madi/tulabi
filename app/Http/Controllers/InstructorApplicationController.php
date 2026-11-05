<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\InstructorApplicationRequest;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Support\InstructorApplications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * «انضم كمدرّس»: a visitor signs up by phone first (the register steps send them back here);
 * someone signed in adds the teaching details. The admin then accepts or rejects.
 */
class InstructorApplicationController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user('web');

        if ($user === null) {
            return redirect()->route('register', ['for' => 'instructor']);
        }

        if ($user->instructor()->exists() || $user->pendingInstructorApplication() !== null) {
            return redirect()->route('account');
        }

        $lastRejected = $user->instructorApplications()
            ->where('status', InstructorApplication::STATUS_REJECTED)
            ->latest('id')
            ->first();

        return Inertia::render('Join/Create', [
            'needsName' => ! $user->hasName(),
            'previous' => $lastRejected?->only(['specialty', 'city', 'bio']),
        ]);
    }

    public function store(InstructorApplicationRequest $request, InstructorApplications $applications): RedirectResponse
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

        return redirect()->route('account')->with('status', __('instructors.submitted'));
    }
}
