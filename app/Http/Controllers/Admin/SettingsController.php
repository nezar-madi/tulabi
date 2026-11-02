<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings', [
            'settings' => [
                'applications_notify_email' => Setting::get(Setting::APPLICATIONS_NOTIFY_EMAIL),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $email = $request->validated('applications_notify_email');

        Setting::put(Setting::APPLICATIONS_NOTIFY_EMAIL, $email === '' ? null : $email);

        return back()->with('status', __('accounts.settings_saved'));
    }
}
