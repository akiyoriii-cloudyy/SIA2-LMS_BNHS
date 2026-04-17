<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionManagementController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::with('roles')->orderBy('name')->paginate(10);

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.permissions.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
            'description' => 'nullable|string|max:255',
            'roles' => 'array',
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        if (! empty($validated['roles'])) {
            $permission->roles()->sync($validated['roles']);
        }

        return redirect()->route('admin.permissions.index')
            ->with('status', 'Permission created successfully.');
    }

    public function show(Permission $permission): View
    {
        $permission->load('roles');

        return view('admin.permissions.show', compact('permission'));
    }

    public function edit(Permission $permission): View
    {
        $roles = Role::orderBy('name')->get();
        $permissionRoles = $permission->roles->pluck('id')->toArray();

        return view('admin.permissions.edit', compact('permission', 'roles', 'permissionRoles'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string|max:255',
            'roles' => 'array',
        ]);

        $permission->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        $permission->roles()->sync($validated['roles'] ?? []);

        return redirect()->route('admin.permissions.index')
            ->with('status', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('status', 'Permission deleted successfully.');
    }

    public function assignToRoles(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => 'array',
        ]);

        $permission->roles()->sync($validated['roles'] ?? []);

        return redirect()->route('admin.permissions.edit', $permission)
            ->with('status', 'Roles assigned successfully.');
    }
}
