<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\Setting;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateService
{
    public function issue(User $user, Course $course): CourseCertificate
    {
        // Idempotente: retornar existente si ya fue emitido
        $existing = CourseCertificate::where([
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ])->first();

        if ($existing) {
            return $existing;
        }

        // Generar número de certificado
        $nextId = (CourseCertificate::max('id') ?? 0) + 1;
        $number = sprintf('CT-%s-%06d', date('Y'), $nextId);

        // Leer configuración de firma
        $signerName           = Setting::get('certificate_signer_name', 'Director Médico');
        $signerTitle          = Setting::get('certificate_signer_title', 'Director de Formación');
        $signatureImageSetting = Setting::get('certificate_signature_image', '');

        // Generar QR como PNG base64
        $verifyUrl  = config('app.url') . '/verify/' . $number;
        $qrBase64   = base64_encode(
            QrCode::format('png')->size(120)->generate($verifyUrl)
        );

        // Ruta de imagen de firma (desde public storage)
        $signatureImagePath = null;
        if ($signatureImageSetting) {
            $signatureImagePath = Storage::disk('public')->path($signatureImageSetting);
        }

        // Logo path
        $logoPath = public_path('logo-negro.png');

        // Fecha de emisión
        $issuedAt = now();

        // Renderizar Blade → PDF
        // chroot = raíz del proyecto para permitir logo (public/) y firma (storage/)
        $options = new Options();
        $options->setChroot(base_path());

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('certificates.template', [
            'userName'           => $user->name,
            'courseTitle'        => $course->title,
            'certificateNumber'  => $number,
            'issuedAt'           => $issuedAt,
            'signerName'         => $signerName,
            'signerTitle'        => $signerTitle,
            'signatureImagePath' => $signatureImagePath,
            'qrBase64'           => $qrBase64,
            'logoPath'           => $logoPath,
        ])->render());
        $pdf->setPaper('a4', 'landscape');
        $pdf->render();

        // Guardar en storage privado
        $filePath = "certificates/{$user->id}/cert-{$course->id}.pdf";
        Storage::disk('private')->put($filePath, $pdf->output());

        // Crear registro en DB
        return CourseCertificate::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'certificate_number' => $number,
            'issued_at'          => $issuedAt,
            'file_path'          => $filePath,
        ]);
    }
}
