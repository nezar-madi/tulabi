<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminActionRequest;
use App\Http\Requests\Admin\RejectApplicationRequest;
use App\Models\Admin;
use App\Models\InstructorApplication;
use App\Support\InstructorApplications;
use App\Support\LibyanPhone;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Instructor applications: pending first; accept (with a WhatsApp welcome) or reject with a reason.
 */
class ApplicationController extends Controller
{
    private const STATUSES = [
        InstructorApplication::STATUS_PENDING,
        InstructorApplication::STATUS_ACCEPTED,
        InstructorApplication::STATUS_REJECTED,
    ];

    public function __construct(private readonly InstructorApplications $applications) {}

    public function index(Request $request): Response
    {
        $status = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ])['status'] ?? InstructorApplication::STATUS_PENDING;

        $applications = InstructorApplication::query()
            ->where('status', $status)
            ->with(['user:id,name,phone,email', 'reviewer:id,name'])
            ->orderBy($status === InstructorApplication::STATUS_PENDING ? 'created_at' : 'reviewed_at', $status === InstructorApplication::STATUS_PENDING ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (InstructorApplication $application) => [
                'id' => $application->id,
                'name' => $application->user->name,
                'phone' => LibyanPhone::display($application->user->phone),
                'email' => $application->user->email,
                'whatsapp' => WhatsApp::link($application->user->phone),
                'specialty' => $application->specialty,
                'city' => $application->city,
                'bio' => $application->bio,
                'status' => $application->status,
                'rejection_reason' => $application->rejection_reason,
                'reviewer' => $application->reviewer?->name,
                'created_at' => $application->created_at?->toIso8601String(),
                'reviewed_at' => $application->reviewed_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Applications/Index', [
            'status' => $status,
            'applications' => $applications,
            'counts' => InstructorApplication::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'welcome' => $request->session()->get('welcome'),
        ]);
    }

    public function accept(AdminActionRequest $request, InstructorApplication $application): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $instructor = $this->applications->accept($application, $admin);

        if ($instructor === null) {
            return back()->withErrors(['application' => __('instructors.already_decided')]);
        }

        $user = $application->user;

        // The page shows its own "accepted" box with the WhatsApp welcome button.
        return back()
            ->with('welcome', [
                'name' => $user->name,
                'whatsapp' => WhatsApp::link($user->phone, InstructorApplications::welcomeMessage($user)),
            ]);
    }

    public function reject(RejectApplicationRequest $request, InstructorApplication $application): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        if (! $this->applications->reject($application, $admin, (string) $request->validated('reason'))) {
            return back()->withErrors(['application' => __('instructors.already_decided')]);
        }

        return back()->with('status', __('instructors.rejected'));
    }
}
