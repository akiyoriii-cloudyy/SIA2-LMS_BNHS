<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionManagementController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()
            ->with('roles')
            ->orderBy('name')
            ->get();

        $archivedPermissions = Permission::onlyTrashed()
            ->with('roles')
            ->orderByDesc('deleted_at')
            ->get();

        return view('admin.permissions.index', compact('permissions', 'archivedPermissions'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.permissions.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->whereNull('deleted_at'),
            ],
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permission->id)->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string|max:255',
            'roles' => 'array',
        ]);

        $permission->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        $permission->roles()->sync(
            $this->permissionRoleSyncIds($permission, $validated['roles'] ?? [])
        );

        return redirect()->route('admin.permissions.index')
            ->with('status', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->withFragment('archived-permissions')
            ->with('status', 'Permission archived. It appears in Archived permissions below—use Restore to bring it back.');
    }

    public function restore(string $id): RedirectResponse
    {
        $permission = Permission::onlyTrashed()->findOrFail($id);

        $conflict = Permission::query()
            ->where('name', $permission->name)
            ->where('id', '!=', $permission->id)
            ->exists();

        if ($conflict) {
            return redirect()->route('admin.permissions.index')
                ->withFragment('archived-permissions')
                ->with('error', 'Cannot restore: another active permission already uses the name "'.$permission->name.'". Rename one of them first.');
        }

        $permission->restore();

        return redirect()->route('admin.permissions.index')
            ->withFragment('active-permissions')
            ->with('status', 'Permission restored successfully. It is back in the active list above.');
    }

    public function assignToRoles(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => 'array',
        ]);

        $permission->roles()->sync(
            $this->permissionRoleSyncIds($permission, $validated['roles'] ?? [])
        );

        return redirect()->route('admin.permissions.edit', $permission)
            ->with('status', 'Roles assigned successfully.');
    }

    /**
     * Keep pivot links for archived (soft-deleted) roles so restoring a role does not lose permissions.
     *
     * @param  list<int|string>  $activeRoleIds
     * @return list<int>
     */
    private function permissionRoleSyncIds(Permission $permission, array $activeRoleIds): array
    {
        $active = array_values(array_unique(array_filter(
            array_map(intval(...), $activeRoleIds),
            fn (int $id): bool => $id > 0
        )));

        $linkedIds = DB::table('role_permissions')
            ->where('permission_id', $permission->id)
            ->pluck('role_id')
            ->all();

        $trashedLinked = Role::onlyTrashed()
            ->whereIn('id', $linkedIds)
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($active, $trashedLinked)));
    }
}
