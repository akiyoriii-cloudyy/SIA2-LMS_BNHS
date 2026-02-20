<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'school_years' => SchoolYear::count(),
            'sections' => Section::count(),
            'subjects' => Subject::count(),
            'teachers' => Teacher::count(),
            'students' => Student::count(),
            'enrollments' => Enrollment::count(),
            'courses' => Course::count(),
        ];

        $quickLinks = [
            ['label' => 'Courses', 'route' => 'courses.index'],
        ];

        if ($user && $user->hasRole('admin', 'teacher')) {
            $quickLinks = array_merge($quickLinks, [
                ['label' => 'Gradebook', 'route' => 'gradebook.index'],
                ['label' => 'Attendance', 'route' => 'attendance.index'],
                ['label' => 'Report Cards', 'route' => 'report-cards.index'],
                ['label' => 'Database Tables', 'route' => 'system.tables'],
            ]);
        }

        return view('dashboard', [
            'stats' => $stats,
            'quickLinks' => $quickLinks,
        ]);
    }

    public function systemTables(): View
    {
        $tables = [
            [
                'table' => 'school_years',
                'purpose' => 'Defines the active school year.',
                'used_in' => 'Courses, Gradebook, Attendance, Report Cards',
            ],
            [
                'table' => 'sections',
                'purpose' => 'Class sections (grade level, track/strand).',
                'used_in' => 'Courses, Gradebook, Attendance, Report Cards',
            ],
            [
                'table' => 'students',
                'purpose' => 'Student master records linked to users.',
                'used_in' => 'Enrollments, Gradebook, Attendance, Report Cards',
            ],
            [
                'table' => 'teachers',
                'purpose' => 'Teacher master records linked to users.',
                'used_in' => 'Subject assignments, Courses',
            ],
            [
                'table' => 'subjects',
                'purpose' => 'Subject catalog.',
                'used_in' => 'Courses, Gradebook, Report Cards',
            ],
            [
                'table' => 'subject_assignments',
                'purpose' => 'Maps teacher + section + subject for a school year.',
                'used_in' => 'Gradebook, Report Cards',
            ],
            [
                'table' => 'enrollments',
                'purpose' => 'Student enrollment per school year + section.',
                'used_in' => 'Gradebook, Attendance, Report Cards',
            ],
            [
                'table' => 'courses',
                'purpose' => 'LMS course shell (per section/subject/year).',
                'used_in' => 'Courses list',
            ],
            [
                'table' => 'grade_entries',
                'purpose' => 'Quarter component grades (quiz/assignment/exam).',
                'used_in' => 'Gradebook',
            ],
            [
                'table' => 'subject_final_grades',
                'purpose' => 'Computed subject averages per enrollment.',
                'used_in' => 'Report Cards',
            ],
            [
                'table' => 'report_cards / report_card_items',
                'purpose' => 'Report card header + subject line items.',
                'used_in' => 'Report Cards',
            ],
            [
                'table' => 'attendance_records',
                'purpose' => 'Daily attendance status per student enrollment.',
                'used_in' => 'Attendance Monitoring + SMS triggers',
            ],
            [
                'table' => 'guardians / guardian_students',
                'purpose' => 'Guardian contacts linked to students.',
                'used_in' => 'SMS notifications for absences',
            ],
            [
                'table' => 'roles / user_roles',
                'purpose' => 'Role-based access control (admin/teacher/student).',
                'used_in' => 'Permissions for all modules',
            ],
            [
                'table' => 'users',
                'purpose' => 'Login accounts and basic profile.',
                'used_in' => 'Authentication',
            ],
        ];

        return view('system-tables', ['tables' => $tables]);
    }
}

