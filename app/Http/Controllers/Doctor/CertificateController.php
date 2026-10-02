<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCertificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function show(Course $course): JsonResponse
    {
        $cert = CourseCertificate::where([
            'user_id'   => auth()->id(),
            'course_id' => $course->id,
        ])->first();

        if (!$cert) {
            return response()->json([
                'message' => 'Aún no tienes certificado para este curso.',
            ], 404);
        }

        return response()->json([
            'certificate_number' => $cert->certificate_number,
            'issued_at'          => $cert->issued_at->toISOString(),
            'download_url'       => route('doctor.certificates.download', $cert->id),
        ]);
    }

    public function download(CourseCertificate $certificate): Response
    {
        if ($certificate->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para descargar este certificado.');
        }

        $content = Storage::disk('private')->get($certificate->file_path);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="certificado-' . $certificate->certificate_number . '.pdf"',
        ]);
    }
}
