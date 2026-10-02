<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_get_certificate_info(): void
    {
        Storage::fake('private');
        $user   = User::factory()->create();
        $course = Course::factory()->create();
        $cert   = CourseCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000001',
            'issued_at'          => now(),
            'file_path'          => 'certificates/1/cert-1.pdf',
        ]);
        Storage::disk('private')->put('certificates/1/cert-1.pdf', 'PDF_CONTENT');

        $response = $this->actingAs($user)
            ->getJson("/api/doctor/courses/{$course->id}/certificate");

        $response->assertOk()
                 ->assertJsonPath('certificate_number', 'CT-2026-000001')
                 ->assertJsonStructure(['certificate_number', 'issued_at', 'download_url']);
    }

    public function test_doctor_cannot_download_another_users_certificate(): void
    {
        $owner  = User::factory()->create();
        $other  = User::factory()->create();
        $course = Course::factory()->create();
        $cert   = CourseCertificate::create([
            'user_id'            => $owner->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000002',
            'issued_at'          => now(),
        ]);

        $this->actingAs($other)
             ->get("/api/doctor/certificates/{$cert->id}/download")
             ->assertForbidden();
    }

    public function test_returns_404_when_no_certificate(): void
    {
        $user   = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($user)
             ->getJson("/api/doctor/courses/{$course->id}/certificate")
             ->assertNotFound();
    }

    public function test_owner_can_download_own_certificate(): void
    {
        Storage::fake('private');
        $user   = User::factory()->create();
        $course = Course::factory()->create();
        $cert   = CourseCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-000003',
            'issued_at'          => now(),
            'file_path'          => 'certificates/1/cert-1.pdf',
        ]);
        Storage::disk('private')->put('certificates/1/cert-1.pdf', '%PDF-1.4 fake content');

        $response = $this->actingAs($user)
            ->get("/api/doctor/certificates/{$cert->id}/download");

        $response->assertOk()
                 ->assertHeader('Content-Type', 'application/pdf');
    }
}
