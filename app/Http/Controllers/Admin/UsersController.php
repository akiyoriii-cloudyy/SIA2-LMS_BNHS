<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->input('status', 'active');
        $status = in_array($status, ['active', 'deleted'], true) ? $status : 'active';

        $query = trim((string) $request->input('q', ''));

        $usersQuery = User::query()
            ->with('roles')
            ->orderBy('name');

        if ($status === 'deleted') {
            $usersQuery->onlyTrashed();
        }

        if ($query !== '') {
            $usersQuery->where(function ($q) use ($query): void {
                $like = '%'.$query.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        return view('admin.users.index', [
            'status' => $status,
            'query' => $query,
            'users' => $usersQuery->paginate(15)->withQueryString(),
            'activeCount' => User::query()->count(),
            'deletedCount' => User::onlyTrashed()->count(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['admin', 'teacher', 'student'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'first_name' => ['nullable', 'string', 'max:255', 'required_if:role,teacher', 'required_if:role,student'],
            'last_name' => ['nullable', 'string', 'max:255', 'required_if:role,teacher', 'required_if:role,student'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
        ]);

        $role = Role::firstOrCreate(['name' => $validated['role']], [
            'description' => ucfirst($validated['role']).' account',
        ]);
        $user->roles()->sync([$role->id]);

        if ($validated['role'] === 'teacher') {
            Teacher::updateOrCreate(
                ['user_id' => $user->id],
                ['first_name' => (string) $validated['first_name'], 'last_name' => (string) $validated['last_name']],
            );
        }

        if ($validated['role'] === 'student') {
            Student::updateOrCreate(
                ['user_id' => $user->id],
                ['first_name' => (string) $validated['first_name'], 'last_name' => (string) $validated['last_name']],
            );
        }

        return back()->with('status', 'User added.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

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

    public function restore(string $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('status', 'User restored.');
    }
}
