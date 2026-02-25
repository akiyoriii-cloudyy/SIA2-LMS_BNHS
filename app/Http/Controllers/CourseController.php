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

        if ($user->hasRole('teacher') && $user->teacher) {
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
