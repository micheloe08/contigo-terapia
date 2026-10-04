<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function show(Lesson $lesson): JsonResponse
    {
        $user = auth()->user();
        $course = $lesson->module->course;

        // Guard de acceso: membresía, compra, o vista previa gratuita
        if (!$lesson->is_free_preview && !$course->isAccessibleFor($user)) {
            return response()->json([
                'message' => 'Sin acceso. Requiere membresía o compra del curso.',
            ], 403);
        }

        $lessonData = $lesson->toArray();

        // Convertir file_path a URL pública
        if ($lesson->file_path) {
            $lessonData['file_url'] = Storage::disk('public')->url($lesson->file_path);
        }

        // Indicar si ya está completada
        $lessonData['is_completed'] = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->whereNotNull('completed_at')
            ->exists();

        // Calcular siguiente lección
        $allLessons = $course->modules->flatMap->lessons->sortBy('order')->values();
        $currentIndex = $allLessons->search(fn($l) => $l->id === $lesson->id);
        $lessonData['next_lesson_id'] = $currentIndex !== false && $currentIndex < $allLessons->count() - 1
            ? $allLessons[$currentIndex + 1]->id
            : null;

        return response()->json($lessonData);
    }

    public function complete(Lesson $lesson): JsonResponse
    {
        $user = auth()->user();
        $course = $lesson->module->course;

        // Guard de acceso
        if (!$lesson->is_free_preview && !$course->isAccessibleFor($user)) {
            return response()->json(['message' => 'Sin acceso al curso.'], 403);
        }

        LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['completed_at' => now()]
        );

        // Calcular progreso del curso
        $course->load('modules.lessons');
        $lessonIds = $course->modules->flatMap->lessons->pluck('id');
        $totalLessons = $lessonIds->count();

        $completedLessons = LessonProgress::whereIn('lesson_id', $lessonIds)
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();

        $percentage = $totalLessons > 0
            ? round($completedLessons / $totalLessons * 100)
            : 0;

        return response()->json([
            'completed'           => true,
            'progress_percentage' => $percentage,
        ]);
    }
}
