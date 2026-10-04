<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function store(Request $request, CourseModule $module): JsonResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'content_type'     => 'required|in:video,file,text',
            'youtube_url'      => [
                'nullable',
                'required_if:content_type,video',
                'url',
                'regex:/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+/',
            ],
            'file'             => 'nullable|required_if:content_type,file|file|mimes:pdf,doc,docx,ppt,pptx,png,jpg,jpeg,zip|max:51200',
            'text_content'     => 'nullable|required_if:content_type,text|string',
            'duration_minutes' => 'nullable|integer|min:0',
            'order'            => 'nullable|integer|min:0',
            'is_free_preview'  => 'nullable|boolean',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['file_path'] = $file->store("courses/{$module->course_id}", 'public');
            $data['file_name'] = $file->getClientOriginalName();
        }

        unset($data['file']);

        $lesson = $module->lessons()->create($data);

        return response()->json($lesson, 201);
    }

    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $data = $request->validate([
            'title'            => 'sometimes|required|string|max:255',
            'description'      => 'nullable|string',
            'content_type'     => 'sometimes|required|in:video,file,text',
            'youtube_url'      => [
                'nullable',
                'url',
                'regex:/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+/',
            ],
            'file'             => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,png,jpg,jpeg,zip|max:51200',
            'text_content'     => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:0',
            'order'            => 'nullable|integer|min:0',
            'is_free_preview'  => 'nullable|boolean',
        ]);

        if ($request->hasFile('file')) {
            // Borrar archivo anterior
            if ($lesson->file_path) {
                Storage::disk('public')->delete($lesson->file_path);
            }
            $file = $request->file('file');
            $data['file_path'] = $file->store("courses/{$lesson->module->course_id}", 'public');
            $data['file_name'] = $file->getClientOriginalName();
        }

        unset($data['file']);

        $lesson->update($data);

        return response()->json($lesson);
    }

    public function destroy(Lesson $lesson): JsonResponse
    {
        $lesson->delete(); // observer borra el archivo del disco
        return response()->json(['message' => 'Lección eliminada']);
    }
}
