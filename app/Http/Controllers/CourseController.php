<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole('student') && $user->student) {
            $enrollments = $user->student->enrollments()->get(['school_year_id', 'section_id']);

            if ($enrollments->isEmpty()) {
                $courses = collect();
            } else {
                $courses = Course::query()
                    ->with(['subject', 'teacher'])
                    ->where(function ($query) use ($enrollments): void {
                        foreach ($enrollments as $enrollment) {
                            $query->orWhere(function ($nested) use ($enrollment): void {
                                $nested->where('school_year_id', $enrollment->school_year_id)
                                    ->where('section_id', $enrollment->section_id);
                            });
                        }
                    })
                    ->orderBy('title')
                    ->get();
            }
        } elseif ($user->hasRole('teacher') && $user->teacher) {
            $courses = Course::query()
                ->with(['subject', 'teacher'])
                ->where('teacher_id', $user->teacher->id)
                ->orderBy('title')
                ->get();
        } else {
            $courses = Course::query()->with(['subject', 'teacher'])->orderBy('title')->get();
        }

        return view('courses.index', ['courses' => $courses]);
    }
}
