<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Support\CourseProgress;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user('student');

        $courses = $student->courses()
            ->with('media')
            ->orderByPivot('purchased_at', 'desc')
            ->get();

        $progress = CourseProgress::percentForCourses($student, $courses);

        return view('student.dashboard', [
            'student' => $student,
            'courses' => $courses,
            'progress' => $progress,
        ]);
    }
}
