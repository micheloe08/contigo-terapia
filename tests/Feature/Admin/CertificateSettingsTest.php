<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_certificate_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::updateOrCreate(['key' => 'certificate_signer_name'],
            ['value' => 'Dr. Test', 'type' => 'string', 'group' => 'certificates', 'label' => 'Nombre', 'is_public' => false]);

        $this->actingAs($admin)
             ->getJson('/api/v1/admin/settings/certificates')
             ->assertOk()
             ->assertJsonStructure(['signer_name', 'signer_title', 'signature_image_url']);
    }

    public function test_admin_can_update_signer_info(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
             ->postJson('/api/v1/admin/settings/certificates', [
                 'signer_name'  => 'Dr. Juan Pérez',
                 'signer_title' => 'Director Médico',
             ])
             ->assertOk();

        $this->assertEquals('Dr. Juan Pérez', Setting::get('certificate_signer_name'));
        $this->assertEquals('Director Médico', Setting::get('certificate_signer_title'));
    }

    public function test_admin_can_upload_signature_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $file  = UploadedFile::fake()->image('firma.png');

        $this->actingAs($admin)
             ->postJson('/api/v1/admin/settings/certificates/signature', [
                 'signature' => $file,
             ])
             ->assertOk()
             ->assertJsonStructure(['signature_image_url']);

        Storage::disk('public')->assertExists('signatures/signature.png');
    }

    public function test_non_admin_cannot_update_certificate_settings(): void
    {
        $doctor = User::factory()->create(['role' => 'doctor']);

        $this->actingAs($doctor)
             ->postJson('/api/v1/admin/settings/certificates', [
                 'signer_name'  => 'Intruder',
                 'signer_title' => 'Bad Actor',
             ])
             ->assertForbidden();
    }

    public function test_signer_name_is_required(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
             ->postJson('/api/v1/admin/settings/certificates', [
                 'signer_title' => 'Director',
             ])
             ->assertUnprocessable();
    }
}
