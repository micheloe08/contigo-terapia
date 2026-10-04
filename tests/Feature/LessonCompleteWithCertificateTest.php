<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseModule;
use App\Models\CourseCertificate;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonCompleteWithCertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_last_lesson_generates_certificate(): void
    {
        Storage::fake('private');

        $user   = User::factory()->create();
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_module_id' => $module->id]);

        CourseEnrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'access_type' => 'free',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/doctor/lessons/{$lesson->id}/complete");

        $response->assertOk()
                 ->assertJsonPath('progress_percentage', 100)
                 ->assertJsonStructure(['certificate' => ['number', 'download_url']]);

        $this->assertDatabaseHas('course_certificates', [
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_completing_non_last_lesson_does_not_generate_certificate(): void
    {
        Storage::fake('private');

        $user   = User::factory()->create();
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['course_module_id' => $module->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['course_module_id' => $module->id, 'order' => 2]);

        CourseEnrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'access_type' => 'free',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/doctor/lessons/{$lesson1->id}/complete");

        $response->assertOk()
                 ->assertJsonMissing(['certificate']);

        $this->assertDatabaseMissing('course_certificates', [
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_completing_course_twice_does_not_duplicate_certificate(): void
    {
        Storage::fake('private');

        $user   = User::factory()->create();
        $course = Course::factory()->create();
        $module = CourseModule::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_module_id' => $module->id]);

        CourseEnrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'access_type' => 'free',
        ]);

        $this->actingAs($user)->postJson("/api/doctor/lessons/{$lesson->id}/complete");
        $this->actingAs($user)->postJson("/api/doctor/lessons/{$lesson->id}/complete");

        $this->assertDatabaseCount('course_certificates', 1);
    }
}
