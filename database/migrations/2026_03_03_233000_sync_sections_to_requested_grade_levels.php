<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            $targetSections = [
                ['grade_level' => 11, 'name' => 'HUMSS', 'strand' => 'HUMSS'],
                ['grade_level' => 11, 'name' => 'ABM', 'strand' => 'ABM'],
                ['grade_level' => 11, 'name' => 'COOKERY/BPP', 'strand' => 'COOKERY/BPP'],
                ['grade_level' => 11, 'name' => 'SMAW', 'strand' => 'SMAW'],
                ['grade_level' => 11, 'name' => 'FOP', 'strand' => 'FOP'],
                ['grade_level' => 11, 'name' => 'CSS', 'strand' => 'CSS'],
                ['grade_level' => 12, 'name' => 'CSS', 'strand' => 'CSS'],
                ['grade_level' => 12, 'name' => 'ABM', 'strand' => 'ABM'],
                ['grade_level' => 12, 'name' => 'SMAW', 'strand' => 'SMAW'],
                ['grade_level' => 12, 'name' => 'FBS', 'strand' => 'FBS'],
                ['grade_level' => 12, 'name' => 'HUMS', 'strand' => 'HUMS'],
                ['grade_level' => 12, 'name' => 'FOP', 'strand' => 'FOP'],
            ];

            $existingIds = DB::table('sections')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            foreach ($targetSections as $index => $section) {
                $track = in_array($section['strand'], ['HUMSS', 'HUMS', 'ABM'], true) ? 'Academic' : 'TVL';

                if (isset($existingIds[$index])) {
                    DB::table('sections')
                        ->where('id', (int) $existingIds[$index])
                        ->update([
                            'name' => $section['name'],
                            'grade_level' => $section['grade_level'],
                            'track' => $track,
                            'strand' => $section['strand'],
                            'updated_at' => $now,
                        ]);
                } else {
                    DB::table('sections')->insert([
                        'name' => $section['name'],
                        'grade_level' => $section['grade_level'],
                        'track' => $track,
                        'strand' => $section['strand'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // This migration intentionally does not rollback section data.
    }
};
