<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CourseCertificate;
use Illuminate\Contracts\View\View;

class CertificateVerifyController extends Controller
{
    public function show(string $certificateNumber): View
    {
        $certificate = CourseCertificate::with(['user', 'course'])
            ->where('certificate_number', $certificateNumber)
            ->first();

        return view('certificates.verify', [
            'certificate'       => $certificate,
            'certificateNumber' => $certificateNumber,
        ]);
    }
}
