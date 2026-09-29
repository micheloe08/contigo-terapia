<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $courses = Course::where('is_published', true)
            ->with(['modules' => fn($q) => $q->with(['lessons' => fn($q) => $q->orderBy('order')])->orderBy('order')])
            ->orderBy('order')
            ->get();

        $courses->each(function ($course) use ($user) {
            $lessonIds = $course->modules->flatMap->lessons->pluck('id');
            $totalLessons = $lessonIds->count();

            $completedLessons = LessonProgress::whereIn('lesson_id', $lessonIds)
                ->where('user_id', $user->id)
                ->whereNotNull('completed_at')
                ->count();

            $course->is_enrolled = CourseEnrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->exists();

            $course->progress_percentage = $totalLessons > 0
                ? round($completedLessons / $totalLessons * 100)
                : 0;

            $course->is_accessible = $course->isAccessibleFor($user);
        });

        return response()->json($courses);
    }

    public function show(Course $course): JsonResponse
    {
        $user = auth()->user();

        $course->load(['modules' => fn($q) => $q->with(['lessons' => fn($q) => $q->orderBy('order')])->orderBy('order')]);

        $lessonIds = $course->modules->flatMap->lessons->pluck('id');
        $totalLessons = $lessonIds->count();

        $completedLessons = LessonProgress::whereIn('lesson_id', $lessonIds)
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();

        // Marcar qué lecciones están completadas
        $completedLessonIds = LessonProgress::whereIn('lesson_id', $lessonIds)
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->toArray();

        $course->modules->each(function ($module) use ($completedLessonIds) {
            $module->lessons->each(function ($lesson) use ($completedLessonIds) {
                $lesson->is_completed = in_array($lesson->id, $completedLessonIds);
            });
        });

        $course->is_enrolled = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        $course->progress_percentage = $totalLessons > 0
            ? round($completedLessons / $totalLessons * 100)
            : 0;

        $course->is_accessible = $course->isAccessibleFor($user);

        return response()->json($course);
    }

    public function enroll(Request $request, Course $course): JsonResponse
    {
        $user = auth()->user();

        if (!$course->isAccessibleFor($user)) {
            return response()->json([
                'message' => 'Requiere membresía o compra del curso',
                'price'   => $course->price,
            ], 403);
        }

        $enrollment = CourseEnrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['access_type' => $user->hasActiveMembership() ? 'membership' : 'free']
        );

        return response()->json([
            'message'    => 'Inscripción exitosa',
            'enrollment' => $enrollment,
        ]);
    }
}
