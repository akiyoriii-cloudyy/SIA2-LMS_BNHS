<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('grade_entries', 'performance_task')) {
                $table->decimal('performance_task', 5, 2)->nullable()->after('quiz');
            }
        });

        if (Schema::hasColumn('grade_entries', 'performance_task') && Schema::hasColumn('grade_entries', 'assignment')) {
            DB::table('grade_entries')
                ->whereNull('performance_task')
                ->update([
                    'performance_task' => DB::raw('assignment'),
                ]);
        }

        Schema::table('subjects', function (Blueprint $table): void {
            if (! Schema::hasColumn('subjects', 'category')) {
                $table->string('category', 20)->default('core')->after('title');
            }
        });

        if (Schema::hasColumn('subjects', 'category')) {
            DB::table('subjects')
                ->whereNull('category')
                ->update([
                    'category' => 'core',
                ]);
        }

        Schema::table('students', function (Blueprint $table): void {
            if (! Schema::hasColumn('students', 'address')) {
                $table->string('address')->nullable()->after('date_of_birth');
            }

            if (! Schema::hasColumn('students', 'ethnicity')) {
                $table->string('ethnicity', 100)->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (Schema::hasColumn('students', 'ethnicity')) {
                $table->dropColumn('ethnicity');
            }

            if (Schema::hasColumn('students', 'address')) {
                $table->dropColumn('address');
            }
        });

        Schema::table('subjects', function (Blueprint $table): void {
            if (Schema::hasColumn('subjects', 'category')) {
                $table->dropColumn('category');
            }
        });

        Schema::table('grade_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('grade_entries', 'performance_task')) {
                $table->dropColumn('performance_task');
            }
        });
    }
};
