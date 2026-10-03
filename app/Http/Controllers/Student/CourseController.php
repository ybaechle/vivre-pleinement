<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Student;
use App\Support\CourseProgress;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function show(Request $request, Course $course): View
    {
        $student = $request->user('student');

        $course->load('modules.lessons');

        return view('student.course', [
            'course' => $course,
            'completedLessonIds' => $this->completedLessonIds($student, $course),
            'progress' => CourseProgress::percent($student, $course),
            'firstLesson' => $course->modules->flatMap->lessons->first(),
        ]);
    }

    public function lesson(Request $request, Course $course, Lesson $lesson): View
    {
        $student = $request->user('student');

        $course->load('modules.lessons');

        return view('student.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'completedLessonIds' => $this->completedLessonIds($student, $course),
            'progress' => CourseProgress::percent($student, $course),
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function completedLessonIds(Student $student, Course $course): array
    {
        return $student->lessonProgress()
            ->whereNotNull('completed_at')
            ->whereIn('lesson_id', $course->lessons()->select('lessons.id'))
            ->pluck('lesson_id')
            ->all();
    }
}
