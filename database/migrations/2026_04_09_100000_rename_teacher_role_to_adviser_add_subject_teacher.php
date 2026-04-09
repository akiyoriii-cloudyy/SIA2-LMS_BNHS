<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $teacherId = DB::table('roles')->where('name', 'teacher')->value('id');
        $adviserId = DB::table('roles')->where('name', 'adviser')->value('id');

        if ($teacherId && $adviserId) {
            foreach (DB::table('user_roles')->where('role_id', $teacherId)->get() as $ur) {
                $hasAdviser = DB::table('user_roles')
                    ->where('user_id', $ur->user_id)
                    ->where('role_id', $adviserId)
                    ->exists();
                if ($hasAdviser) {
                    DB::table('user_roles')->where('id', $ur->id)->delete();
                } else {
                    DB::table('user_roles')->where('id', $ur->id)->update([
                        'role_id' => $adviserId,
                        'updated_at' => now(),
                    ]);
                }
            }
            DB::table('role_permissions')->where('role_id', $teacherId)->delete();
            DB::table('roles')->where('id', $teacherId)->delete();
        } elseif ($teacherId && ! $adviserId) {
            DB::table('roles')
                ->where('id', $teacherId)
                ->update([
                    'name' => 'adviser',
                    'description' => 'Class adviser — homeroom, records, attendance, and full instructional dashboard',
                    'updated_at' => now(),
                ]);
        }

        if (DB::table('roles')->where('name', 'subject_teacher')->exists()) {
            return;
        }

        $level = (int) (DB::table('roles')->where('name', 'adviser')->value('level') ?: 200);

        $subjectTeacherRoleId = DB::table('roles')->insertGetId([
            'name' => 'subject_teacher',
            'description' => 'Subject teacher — encode grades only for assigned subjects',
            'level' => $level,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionNames = [
            'dashboard.view',
            'gradebook.view',
            'gradebook.edit',
            'settings.manage_own',
        ];

        $permIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        $rows = $permIds->map(fn (int $pid): array => [
            'role_id' => $subjectTeacherRoleId,
            'permission_id' => $pid,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if ($rows !== []) {
            DB::table('role_permissions')->insert($rows);
        }
    }

    public function down(): void
    {
        $stId = DB::table('roles')->where('name', 'subject_teacher')->value('id');
        if ($stId) {
            DB::table('role_permissions')->where('role_id', $stId)->delete();
            DB::table('roles')->where('id', $stId)->delete();
        }

        DB::table('roles')
            ->where('name', 'adviser')
            ->update([
                'name' => 'teacher',
                'description' => 'Subject teacher (editor)',
                'updated_at' => now(),
            ]);
    }
};
