<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * RBAC baseline:
     * - Roles: admin > adviser (class/homeroom) > subject_teacher (encoding only) > user
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
            'roles.manage' => 'Manage roles and permissions',
            'permissions.manage' => 'Manage system permissions',
            'activity_logs.view' => 'View activity logs and audit trail',
            'activity_logs.manage' => 'Manage and terminate user sessions',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name], ['description' => $description]);
        }

        $admin = Role::updateOrCreate(['name' => 'admin'], ['description' => 'School administrator', 'level' => 300]);
        $adviser = Role::updateOrCreate(['name' => 'adviser'], ['description' => 'Class adviser / homeroom teacher', 'level' => 200]);
        $subjectTeacher = Role::updateOrCreate(['name' => 'subject_teacher'], ['description' => 'Subject teacher (grade encoding for assigned subjects)', 'level' => 200]);
        $user = Role::updateOrCreate(['name' => 'user'], ['description' => 'Limited user', 'level' => 100]);

        $admin->permissions()->sync(Permission::query()->pluck('id')->all());

        $adviser->permissions()->sync(Permission::query()->whereIn('name', [
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
            'activity_logs.view',
        ])->pluck('id')->all());

        $subjectTeacher->permissions()->sync(Permission::query()->whereIn('name', [
            'dashboard.view',
            'gradebook.view',
            'gradebook.edit',
            'settings.manage_own',
            'activity_logs.view',
        ])->pluck('id')->all());

        $user->permissions()->sync(Permission::query()->whereIn('name', [
            'dashboard.view',
            'courses.view',
        ])->pluck('id')->all());
    }
}
