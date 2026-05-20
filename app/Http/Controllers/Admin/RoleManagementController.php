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

class RoleManagementController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->with('parent', 'permissions')
            ->withCount('users')
            ->orderBy('level', 'desc')
            ->get();

        $archivedRoles = Role::onlyTrashed()
            ->with('parent', 'permissions')
            ->withCount('users')
            ->orderByDesc('deleted_at')
            ->get();

        return view('admin.roles.index', compact('roles', 'archivedRoles'));
    }

    public function create(): View
    {
        $permissions = Permission::orderBy('name')->get();
        $hierarchyRoles = $this->hierarchyRoleOptions();

        return view('admin.roles.create', compact('permissions', 'hierarchyRoles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'parent_id' => $request->filled('parent_id') ? $request->integer('parent_id') : null,
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string|max:255',
            'parent_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->whereNull('deleted_at')],
            'level' => [
                Rule::requiredIf(fn () => $request->input('parent_id') === null),
                'nullable',
                'integer',
                'min:0',
            ],
            'permissions' => 'array',
        ]);

        $parentId = isset($validated['parent_id']) && $validated['parent_id'] !== null
            ? (int) $validated['parent_id']
            : null;

        if ($parentId !== null) {
            $level = (int) Role::query()->findOrFail($parentId)->level;
        } else {
            $level = (int) $validated['level'];
        }

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'parent_id' => $parentId,
            'level' => $level,
        ]);

        if (! empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')
            ->with('status', 'Role created successfully.');
    }

    public function show(Role $role): View
    {
        $role->load('parent', 'permissions', 'users');

        return view('admin.roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        $hierarchyRoles = $this->hierarchyRoleOptions($role);

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions', 'hierarchyRoles'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $request->merge([
            'parent_id' => $request->filled('parent_id') ? $request->integer('parent_id') : null,
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role->id)->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string|max:255',
            'parent_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->whereNull('deleted_at')],
            'level' => [
                Rule::requiredIf(fn () => $request->input('parent_id') === null),
                'nullable',
                'integer',
                'min:0',
            ],
            'permissions' => 'array',
        ]);

        $parentId = isset($validated['parent_id']) && $validated['parent_id'] !== null
            ? (int) $validated['parent_id']
            : null;
        if ($role->parentWouldCreateCycle($parentId)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['parent_id' => 'That parent would create a circular hierarchy. Choose another parent or leave top-level.']);
        }

        if ($parentId !== null) {
            $level = (int) Role::query()->findOrFail($parentId)->level;
        } else {
            $level = (int) $validated['level'];
        }

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'parent_id' => $parentId,
            'level' => $level,
        ]);

        $role->permissions()->sync(
            $this->rolePermissionSyncIds($role, $validated['permissions'] ?? [])
        );

        return redirect()->route('admin.roles.index')
            ->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->withFragment('archived-roles')
            ->with('status', 'Role archived. It appears in Archived roles below—use Restore to bring it back to the active list.');
    }

    public function restore(string $id): RedirectResponse
    {
        $role = Role::onlyTrashed()->findOrFail($id);

        $conflict = Role::query()
            ->where('name', $role->name)
            ->where('id', '!=', $role->id)
            ->exists();

        if ($conflict) {
            return redirect()->route('admin.roles.index')
                ->withFragment('archived-roles')
                ->with('error', 'Cannot restore: another active role already uses the name "'.$role->name.'". Rename the active role or the archived one first.');
        }

        $role->restore();

        return redirect()->route('admin.roles.index')
            ->withFragment('active-roles')
            ->with('status', 'Role restored successfully. It is back in the active list above.');
    }

    public function assignPermissions(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => 'array',
        ]);

        $role->permissions()->sync(
            $this->rolePermissionSyncIds($role, $validated['permissions'] ?? [])
        );

        return redirect()->route('admin.roles.edit', $role)
            ->with('status', 'Permissions assigned successfully.');
    }

    /**
     * Keep pivot links for archived (soft-deleted) permissions so restoring a permission does not drop role assignments.
     *
     * @param  list<int|string>  $activePermissionIds
     * @return list<int>
     */
    private function rolePermissionSyncIds(Role $role, array $activePermissionIds): array
    {
        $active = array_values(array_unique(array_filter(
            array_map(intval(...), $activePermissionIds),
            fn (int $id): bool => $id > 0
        )));

        $linkedIds = DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->pluck('permission_id')
            ->all();

        $trashedLinked = Permission::onlyTrashed()
            ->whereIn('id', $linkedIds)
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($active, $trashedLinked)));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    private function hierarchyRoleOptions(?Role $exclude = null)
    {
        $query = Role::query()
            ->orderByDesc('level')
            ->orderBy('name');

        if ($exclude !== null) {
            $excludeIds = $this->descendantRoleIds($exclude);
            $excludeIds[] = $exclude->id;
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->get();
    }

    /**
     * @return list<int>
     */
    private function descendantRoleIds(Role $role): array
    {
        $ids = [];
        $stack = $role->children()->pluck('id')->all();
        while ($stack !== []) {
            $id = (int) array_pop($stack);
            $ids[] = $id;
            $childIds = Role::query()->where('parent_id', $id)->pluck('id')->all();
            foreach ($childIds as $childId) {
                $stack[] = (int) $childId;
            }
        }

        return $ids;
    }
}
