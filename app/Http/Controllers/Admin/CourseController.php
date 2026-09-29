<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        $courses = Course::withCount(['modules', 'enrollments'])
            ->with(['modules' => fn($q) => $q->withCount('lessons')])
            ->orderBy('order')
            ->paginate(15);

        return response()->json($courses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'nullable|numeric|min:0',
            'is_published' => 'nullable|boolean',
            'order'        => 'nullable|integer|min:0',
            'thumbnail'    => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')
                ->store('courses/thumbnails', 'public');
        }

        $course = Course::create($data);

        return response()->json($course, 201);
    }

    public function show(Course $course): JsonResponse
    {
        $course->load(['modules.lessons']);
        return response()->json($course);
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'nullable|numeric|min:0',
            'is_published' => 'nullable|boolean',
            'order'        => 'nullable|integer|min:0',
            'thumbnail'    => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            // Borrar thumbnail anterior
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')
                ->store('courses/thumbnails', 'public');
        }

        $course->update($data);

        return response()->json($course);
    }

    public function destroy(Course $course): JsonResponse
    {
        $course->delete(); // soft delete
        return response()->json(['message' => 'Curso eliminado']);
    }
}
