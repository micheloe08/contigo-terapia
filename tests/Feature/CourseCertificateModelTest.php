<?php

namespace Tests\Feature;

use App\Models\CourseCertificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCertificateModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_has_unique_user_course_constraint(): void
    {
        $user   = User::factory()->create();
        $course = Course::factory()->create();

        CourseCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000001',
            'issued_at'          => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CourseCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000002',
            'issued_at'          => now(),
        ]);
    }

    public function test_certificate_number_is_unique(): void
    {
        $user1  = User::factory()->create();
        $user2  = User::factory()->create();
        $course = Course::factory()->create();

        CourseCertificate::create([
            'user_id'            => $user1->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000001',
            'issued_at'          => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CourseCertificate::create([
            'user_id'            => $user2->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000001',
            'issued_at'          => now(),
        ]);
    }

    public function test_certificate_belongs_to_user_and_course(): void
    {
        $user   = User::factory()->create();
        $course = Course::factory()->create();

        $cert = CourseCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000001',
            'issued_at'          => now(),
        ]);

        $this->assertTrue($cert->user->is($user));
        $this->assertTrue($cert->course->is($course));
    }

    public function test_issued_at_is_cast_to_datetime(): void
    {
        $user   = User::factory()->create();
        $course = Course::factory()->create();

        $cert = CourseCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000001',
            'issued_at'          => '2026-10-02 07:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $cert->issued_at);
    }

    public function test_file_path_is_nullable(): void
    {
        $user   = User::factory()->create();
        $course = Course::factory()->create();

        $cert = CourseCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000001',
            'issued_at'          => now(),
            'file_path'          => null,
        ]);

        $this->assertNull($cert->file_path);
    }
}
