<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\Setting;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_creates_certificate_and_pdf_file(): void
    {
        Storage::fake('private');
        $user   = User::factory()->create(['name' => 'Ana García López']);
        $course = Course::factory()->create(['title' => 'Terapia Cognitivo Conductual']);

        $cert = app(CertificateService::class)->issue($user, $course);

        $this->assertDatabaseHas('course_certificates', [
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);
        $this->assertStringStartsWith('CT-', $cert->certificate_number);
        Storage::disk('private')->assertExists($cert->file_path);
    }

    public function test_issue_is_idempotent(): void
    {
        Storage::fake('private');
        $user   = User::factory()->create();
        $course = Course::factory()->create();

        $cert1 = app(CertificateService::class)->issue($user, $course);
        $cert2 = app(CertificateService::class)->issue($user, $course);

        $this->assertEquals($cert1->id, $cert2->id);
        $this->assertEquals($cert1->certificate_number, $cert2->certificate_number);
        $this->assertDatabaseCount('course_certificates', 1);
    }

    public function test_issue_generates_pdf_without_signature_image(): void
    {
        Storage::fake('private');
        Setting::updateOrCreate(
            ['key' => 'certificate_signature_image'],
            ['value' => '', 'type' => 'string', 'group' => 'certificates', 'label' => 'Firma', 'is_public' => false]
        );
        $user   = User::factory()->create();
        $course = Course::factory()->create();

        $cert = app(CertificateService::class)->issue($user, $course);

        $this->assertNotNull($cert->file_path);
    }

    public function test_certificate_number_format_is_correct(): void
    {
        Storage::fake('private');
        $user   = User::factory()->create();
        $course = Course::factory()->create();

        $cert = app(CertificateService::class)->issue($user, $course);

        $this->assertMatchesRegularExpression('/^CT-\d{4}-\d{6}$/', $cert->certificate_number);
    }
}
