<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminActionRequest;
use App\Models\Admin;
use App\Models\PasswordResetLink;
use App\Models\User;
use App\Support\LibyanPhone;
use App\Support\PasswordResetLinks;
use App\Support\WhatsApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin's account lists (instructors, students): search, suspend or activate the
 * account, and make a password reset link to send by WhatsApp.
 */
class UserController extends Controller
{
    public function instructors(Request $request): Response
    {
        return $this->list($request, 'instructors');
    }

    public function students(Request $request): Response
    {
        return $this->list($request, 'students');
    }

    public function suspend(AdminActionRequest $request, User $user): RedirectResponse
    {
        return $this->setStatus($user, User::STATUS_SUSPENDED);
    }

    public function activate(AdminActionRequest $request, User $user): RedirectResponse
    {
        return $this->setStatus($user, User::STATUS_ACTIVE);
    }

    public function resetLink(AdminActionRequest $request, User $user, PasswordResetLinks $links): RedirectResponse
    {
        if (! $user->isActive()) {
            return back()->withErrors(['user' => __('accounts.reset.account_suspended')]);
        }

        /** @var Admin $admin */
        $admin = $request->user('admin');
        $url = $links->issue($user, PasswordResetLink::ISSUER_ADMIN, $admin->getKey());

        return back()->with('resetLink', [
            'name' => $user->name,
            'url' => $url,
            'whatsapp' => WhatsApp::link($user->phone, __('accounts.reset.whatsapp', ['name' => $user->name, 'url' => $url])),
        ]);
    }

    private function list(Request $request, string $kind): Response
    {
        $search = trim((string) ($request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? ''));

        $users = User::query()
            ->when($kind === 'instructors', fn (Builder $query) => $query->whereHas('instructor'))
            ->when($kind === 'students', fn (Builder $query) => $query->whereDoesntHave('instructor'))
            ->when($search !== '', fn (Builder $query) => $this->search($query, $search))
            ->with('instructor:id,user_id,specialty,city')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => LibyanPhone::display($user->phone),
                'email' => $user->email,
                'status' => $user->status,
                'specialty' => $user->instructor?->specialty,
                'city' => $user->instructor?->city,
                'whatsapp' => WhatsApp::link($user->phone),
                'created_at' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'kind' => $kind,
            'users' => $users,
            'search' => $search,
            'resetLink' => $request->session()->get('resetLink'),
        ]);
    }

    /** @param  Builder<User>  $query */
    private function search(Builder $query, string $search): void
    {
        $phone = LibyanPhone::normalize($search);
        $like = '%'.addcslashes($search, '%_\\').'%';

        $query->where(function (Builder $query) use ($phone, $like) {
            $query->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);

            if ($phone !== null) {
                $query->orWhere('phone', $phone);
            }
        });
    }

    private function setStatus(User $user, string $status): RedirectResponse
    {
        $user->forceFill(['status' => $status])->save();

        return back()->with('status', __('accounts.status_changed.'.$status));
    }
}
