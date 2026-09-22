<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorApprovalController extends Controller
{
    /**
     * Lista terapeutas con filtro por status.
     * GET /api/v1/supervisor/doctors?status=pending
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');

        $doctors = Doctor::with(['user', 'specialty'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($doctors);
    }

    /**
     * Detalle de un terapeuta.
     * GET /api/v1/supervisor/doctors/{doctor}
     */
    public function show(Doctor $doctor): JsonResponse
    {
        $doctor->load(['user', 'specialty']);
        return response()->json($doctor);
    }

    /**
     * Aprobar un terapeuta.
     * POST /api/v1/supervisor/doctors/{doctor}/approve
     */
    public function approve(Request $request, Doctor $doctor): JsonResponse
    {
        if ($doctor->status === 'approved') {
            return response()->json(['message' => 'El terapeuta ya está aprobado.'], 422);
        }

        $doctor->update([
            'status'           => 'approved',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Terapeuta aprobado exitosamente.',
            'doctor'  => $doctor->fresh(['user', 'specialty']),
        ]);
    }

    /**
     * Rechazar un terapeuta.
     * POST /api/v1/supervisor/doctors/{doctor}/reject
     */
    public function reject(Request $request, Doctor $doctor): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        if ($doctor->status === 'rejected') {
            return response()->json(['message' => 'El terapeuta ya está rechazado.'], 422);
        }

        $doctor->update([
            'status'           => 'rejected',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'message' => 'Terapeuta rechazado.',
            'doctor'  => $doctor->fresh(['user', 'specialty']),
        ]);
    }

    /**
     * Estadísticas para el dashboard de admin.
     * GET /api/v1/supervisor/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'pending'  => Doctor::where('status', 'pending')->count(),
            'approved' => Doctor::where('status', 'approved')->count(),
            'rejected' => Doctor::where('status', 'rejected')->count(),
            'total'    => Doctor::count(),
            'patients' => Patient::count(),
        ]);
    }
}
