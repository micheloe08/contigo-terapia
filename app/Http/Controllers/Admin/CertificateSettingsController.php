<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateSettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $imagePath = Setting::get('certificate_signature_image', '');
        $imageUrl  = $imagePath
            ? Storage::disk('public')->url($imagePath)
            : null;

        return response()->json([
            'signer_name'          => Setting::get('certificate_signer_name', ''),
            'signer_title'         => Setting::get('certificate_signer_title', ''),
            'signature_image_url'  => $imageUrl,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signer_name'  => 'required|string|max:100',
            'signer_title' => 'required|string|max:100',
        ]);

        Setting::set('certificate_signer_name', $validated['signer_name']);
        Setting::set('certificate_signer_title', $validated['signer_title']);

        return response()->json(['message' => 'Configuración de certificado actualizada.']);
    }

    public function uploadSignature(Request $request): JsonResponse
    {
        $request->validate([
            'signature' => 'required|image|mimes:png|max:2048',
        ]);

        $path = $request->file('signature')->storeAs('signatures', 'signature.png', 'public');

        Setting::set('certificate_signature_image', $path);

        return response()->json([
            'message'               => 'Imagen de firma actualizada.',
            'signature_image_url'   => Storage::disk('public')->url($path),
        ]);
    }
}
