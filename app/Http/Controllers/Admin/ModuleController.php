<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModuleController extends Controller
{
    public function store(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'order'       => 'nullable|integer|min:0',
        ]);

        $module = $course->modules()->create($data);

        return response()->json($module->load('lessons'), 201);
    }

    public function update(Request $request, CourseModule $module): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order'       => 'nullable|integer|min:0',
        ]);

        $module->update($data);

        return response()->json($module);
    }

    public function destroy(CourseModule $module): JsonResponse
    {
        // Las lecciones se borran en cascade (observer borra archivos)
        $module->lessons->each->delete();
        $module->delete();

        return response()->json(['message' => 'Módulo eliminado']);
    }
}
