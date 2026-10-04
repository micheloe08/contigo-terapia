<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    protected $fillable = [
        'course_module_id',
        'title',
        'description',
        'content_type',
        'youtube_url',
        'file_path',
        'file_name',
        'text_content',
        'duration_minutes',
        'order',
        'is_free_preview',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'order' => 'integer',
        'is_free_preview' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Lesson $lesson) {
            if ($lesson->file_path) {
                Storage::disk('public')->delete($lesson->file_path);
            }
        });
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
