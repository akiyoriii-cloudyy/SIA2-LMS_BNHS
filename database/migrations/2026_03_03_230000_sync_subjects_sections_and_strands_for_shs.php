<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->syncSections();
            $this->syncSubjects();
        });
    }

    public function down(): void
    {
        // Data sync migration intentionally has no destructive rollback.
    }

    private function syncSections(): void
    {
        $now = now();

        // Normalize old demo section name to the new label used in dropdowns.
        DB::table('sections')
            ->where('name', 'HUMSS-A')
            ->update([
                'name' => 'HUMSS Section A',
                'grade_level' => 11,
                'track' => 'Academic',
                'strand' => 'HUMSS',
                'updated_at' => $now,
            ]);

        $sections = [
            ['name' => 'HUMSS Section A', 'grade_level' => 11, 'track' => 'Academic', 'strand' => 'HUMSS'],
            ['name' => 'HUMSS Section B', 'grade_level' => 11, 'track' => 'Academic', 'strand' => 'HUMSS'],
            ['name' => 'ABM Section A', 'grade_level' => 11, 'track' => 'Academic', 'strand' => 'ABM'],
            ['name' => 'STEM Section A', 'grade_level' => 11, 'track' => 'Academic', 'strand' => 'STEM'],
        ];

        foreach ($sections as $section) {
            DB::table('sections')->updateOrInsert(
                [
                    'name' => $section['name'],
                    'grade_level' => $section['grade_level'],
                ],
                [
                    'track' => $section['track'],
                    'strand' => $section['strand'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    private function syncSubjects(): void
    {
        $now = now();

        $subjects = [
            ['code' => 'ORALCOMM', 'title' => 'Oral Communication in Context'],
            ['code' => 'KOMPAN', 'title' => 'Komunikasyon at Pananaliksik'],
            ['code' => '21CLIT', 'title' => '21st Century Literature'],
            ['code' => 'CPAR', 'title' => 'Contemporary Philippine Arts'],
            ['code' => 'MIL', 'title' => 'Media and Information Literacy'],
            ['code' => 'PERDEV', 'title' => 'Personal Development'],
            ['code' => 'ELS', 'title' => 'Earth and Life Science'],
            ['code' => 'PEH', 'title' => 'Physical Education and Health'],
        ];

        // Remap legacy demo codes to requested SHS subjects to preserve existing relations where possible.
        $legacyCodeToNewCode = [
            'ENG11' => 'ORALCOMM',
            'MATH11' => 'KOMPAN',
            'SCI11' => 'ELS',
        ];

        foreach ($legacyCodeToNewCode as $legacyCode => $newCode) {
            $newSubject = collect($subjects)->firstWhere('code', $newCode);
            if (! $newSubject) {
                continue;
            }

            $legacyRow = DB::table('subjects')->where('code', $legacyCode)->first();
            $newCodeExists = DB::table('subjects')->where('code', $newCode)->exists();

            if ($legacyRow && ! $newCodeExists) {
                DB::table('subjects')
                    ->where('id', $legacyRow->id)
                    ->update([
                        'code' => $newSubject['code'],
                        'title' => $newSubject['title'],
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);
            }
        }

        foreach ($subjects as $subject) {
            DB::table('subjects')->updateOrInsert(
                ['code' => $subject['code']],
                [
                    'title' => $subject['title'],
                    'deleted_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
};
