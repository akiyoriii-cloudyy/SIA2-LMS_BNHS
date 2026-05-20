<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'lms.portal')->value('id');
        if ($permissionId === null) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'lms.portal',
                'description' => 'Staff access to LMS web and API (sign-in gate)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (['admin', 'adviser', 'subject_teacher'] as $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->whereNull('deleted_at')->value('id');
            if ($roleId === null) {
                continue;
            }
            $exists = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'lms.portal')->value('id');
        if ($permissionId === null) {
            return;
        }
        DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
