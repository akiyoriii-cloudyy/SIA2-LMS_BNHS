<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\InAppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function __construct(
        private readonly InAppNotificationService $inAppNotifications,
    ) {}

    public function index(Request $request): View
    {
        $status = (string) $request->input('status', 'active');
        $status = in_array($status, ['active', 'deleted'], true) ? $status : 'active';

        $query = trim((string) $request->input('q', ''));

        $usersQuery = User::query()
            ->with(['roles', 'profile'])
            ->orderBy('name');

        if ($status === 'deleted') {
            $usersQuery->onlyTrashed();
        }

        if ($query !== '') {
            $usersQuery->where(function ($q) use ($query): void {
                $like = '%'.$query.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('profile', function ($profileQuery) use ($like): void {
                        $profileQuery->where('first_name', 'like', $like)
                            ->orWhere('middle_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like);
                    });
            });
        }

        return view('admin.users.index', [
            'status' => $status,
            'query' => $query,
            'users' => $usersQuery->paginate(10)->withQueryString(),
            'activeCount' => User::query()->count(),
            'deletedCount' => User::onlyTrashed()->count(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['admin', 'adviser', 'subject_teacher'])],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
        ]);

        $fullName = trim(implode(' ', array_filter([
            $validated['first_name'] ?? null,
            $validated['middle_name'] ?? null,
            $validated['last_name'] ?? null,
            $validated['suffix'] ?? null,
        ])));

        $user = User::query()->create([
            'name' => $fullName,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
        ]);

        $role = Role::firstOrCreate(['name' => $validated['role']], [
            'description' => ucfirst($validated['role']).' account',
        ]);
        $user->roles()->sync([$role->id]);

        if (in_array($validated['role'], ['adviser', 'subject_teacher'], true)) {
            Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => (string) $validated['first_name'],
                    'last_name' => (string) $validated['last_name'],
                ],
            );
        }

        $display = $user->display_name ?: $user->name;
        $this->inAppNotifications->notifyAllAdmins(
            'user_management',
            'New user account',
            "A new {$validated['role']} account was created for {$display} ({$user->email}).",
            [
                'actor_id' => $request->user()->id,
                'actor_name' => (string) ($request->user()->display_name ?: $request->user()->name),
                'user_id' => $user->id,
            ],
        );

        return back()->with('status', 'User added.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['admin', 'adviser', 'subject_teacher'])],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
        ]);

        $fullName = trim(implode(' ', array_filter([
            $validated['first_name'] ?? null,
            $validated['middle_name'] ?? null,
            $validated['last_name'] ?? null,
            $validated['suffix'] ?? null,
        ])));

        $user->update([
            'name' => $fullName,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        UserProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
            ]
        );

        $role = Role::firstOrCreate(['name' => $validated['role']], [
            'description' => ucfirst($validated['role']).' account',
        ]);
        $user->roles()->sync([$role->id]);

        if (in_array($validated['role'], ['adviser', 'subject_teacher'], true)) {
            Teacher::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => (string) $validated['first_name'],
                    'last_name' => (string) $validated['last_name'],
                ]
            );
        } else {
            Teacher::query()->where('user_id', $user->id)->delete();
        }

        $display = $user->display_name ?: $user->name;
        $this->inAppNotifications->notifyAllAdmins(
            'user_management',
            'User updated',
            "User account for {$display} ({$user->email}) was updated; role is now {$validated['role']}.",
            [
                'actor_id' => $request->user()->id,
                'actor_name' => (string) ($request->user()->display_name ?: $request->user()->name),
                'user_id' => $user->id,
            ],
        );

        return back()->with('status', 'User updated.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $label = $user->display_name ?: $user->name;
        $userId = $user->id;
        $user->delete();

        $this->inAppNotifications->notifyAllAdmins(
            'user_management',
            'User deleted',
            "User account for {$label} was deactivated (soft deleted).",
            [
                'actor_id' => $request->user()->id,
                'actor_name' => (string) ($request->user()->display_name ?: $request->user()->name),
                'user_id' => $userId,
            ],
        );

        return back()->with('status', 'User deleted.');
    }

    public function updatePassword(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::withTrashed()->findOrFail($id);
        $user->update(['password' => $validated['password']]);

        return back()->with('status', 'Password updated.');
    }

    public function restore(Request $request, string $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        $display = $user->display_name ?: $user->name;
        $this->inAppNotifications->notifyAllAdmins(
            'user_management',
            'User restored',
            "User account for {$display} was restored.",
            [
                'actor_id' => $request->user()->id,
                'actor_name' => (string) ($request->user()->display_name ?: $request->user()->name),
                'user_id' => $user->id,
            ],
        );

        return back()->with('status', 'User restored.');
    }
}
