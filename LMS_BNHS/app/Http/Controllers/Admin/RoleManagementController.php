<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->orderBy('level', 'desc')->paginate(10);

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::orderBy('name')->get();

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string|max:255',
            'level' => 'required|integer|min:0',
            'permissions' => 'array',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'level' => $validated['level'],
        ]);

        if (! empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')
            ->with('status', 'Role created successfully.');
    }

    public function show(Role $role): View
    {
        $role->load('permissions', 'users');

        return view('admin.roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:255',
            'level' => 'required|integer|min:0',
            'permissions' => 'array',
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'level' => $validated['level'],
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete role with assigned users.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('status', 'Role deleted successfully.');
    }

    public function assignPermissions(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => 'array',
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.edit', $role)
            ->with('status', 'Permissions assigned successfully.');
    }
}
