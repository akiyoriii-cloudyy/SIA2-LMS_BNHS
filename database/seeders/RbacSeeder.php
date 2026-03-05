<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * RBAC baseline:
     * - Roles: admin > teacher (editor) > user
     * - Permissions: used by `permission` middleware on routes
     */
    public function run(): void
    {
        $permissions = [
            'dashboard.view' => 'View dashboard',
            'courses.view' => 'View courses',
            'gradebook.view' => 'View gradebook',
            'gradebook.edit' => 'Edit grades',
            'records.manage' => 'Manage student/subject records',
            'attendance.manage' => 'Manage attendance records',
            'report_cards.view' => 'View report cards',
            'report_cards.edit' => 'Edit report card observed values',
            'sms_logs.view' => 'View SMS logs',
            'settings.manage_own' => 'Manage own profile/settings',
            'users.manage' => 'Manage users',
            'settings.manage' => 'Manage admin settings',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name], ['description' => $description]);
        }

        $admin = Role::updateOrCreate(['name' => 'admin'], ['description' => 'School administrator', 'level' => 300]);
        $teacher = Role::updateOrCreate(['name' => 'teacher'], ['description' => 'Subject teacher (editor)', 'level' => 200]);
        $user = Role::updateOrCreate(['name' => 'user'], ['description' => 'Limited user', 'level' => 100]);

        $admin->permissions()->sync(Permission::query()->pluck('id')->all());
        $teacher->permissions()->sync(Permission::query()->whereIn('name', [
            'dashboard.view',
            'courses.view',
            'gradebook.view',
            'gradebook.edit',
            'records.manage',
            'attendance.manage',
            'report_cards.view',
            'report_cards.edit',
            'sms_logs.view',
            'settings.manage_own',
        ])->pluck('id')->all());
        $user->permissions()->sync(Permission::query()->whereIn('name', [
            'dashboard.view',
            'courses.view',
        ])->pluck('id')->all());
    }
}

