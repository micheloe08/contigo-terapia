<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateVerifyPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_verify_shows_valid_certificate(): void
    {
        $user   = User::factory()->create(['name' => 'Ana García']);
        $course = Course::factory()->create(['title' => 'Terapia TCC']);
        CourseCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'certificate_number' => 'CT-2026-999999',
            'issued_at'          => now(),
        ]);

        $this->get('/verify/CT-2026-999999')
             ->assertOk()
             ->assertSee('Ana García')
             ->assertSee('Terapia TCC')
             ->assertSee('CT-2026-999999');
    }

    public function test_public_verify_returns_not_found_for_invalid_number(): void
    {
        $this->get('/verify/CT-0000-000000')
             ->assertOk()
             ->assertSee('no encontrado');
    }

    public function test_public_verify_accessible_without_authentication(): void
    {
        // Should return 200 (not redirect to login) even without auth
        $this->get('/verify/CT-2026-000000')
             ->assertOk();
    }
}
