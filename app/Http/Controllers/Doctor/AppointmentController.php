<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * GET /api/v1/doctor/appointments
     * Lista las citas del doctor autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'all');
        $date   = $request->query('date');

        $query = $request->user()->doctor
            ->appointments()
            ->with(['patient.user:id,name,email'])
            ->orderBy('starts_at', 'asc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($date) {
            $query->whereDate('starts_at', $date);
        }

        return response()->json($query->paginate(15));
    }

    /**
     * GET /api/v1/doctor/appointments/{appointment}
     */
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->doctor_id !== $request->user()->doctor->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $appointment->load(['patient.user:id,name,email']);

        return response()->json($appointment);
    }

    /**
     * POST /api/v1/doctor/appointments/{appointment}/confirm
     */
    public function confirm(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->doctor_id !== $request->user()->doctor->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($appointment->status !== AppointmentStatus::Pending) {
            return response()->json(['message' => 'Solo se pueden confirmar citas pendientes.'], 422);
        }

        $appointment->update(['status' => AppointmentStatus::Confirmed]);

        return response()->json(['message' => 'Cita confirmada.', 'appointment' => $appointment->fresh()]);
    }

    /**
     * POST /api/v1/doctor/appointments/{appointment}/cancel
     */
    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->doctor_id !== $request->user()->doctor->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (!in_array($appointment->status->value, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'Esta cita no puede cancelarse.'], 422);
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:300',
        ]);

        $appointment->cancel($data['reason'] ?? null);

        return response()->json(['message' => 'Cita cancelada.', 'appointment' => $appointment->fresh()]);
    }

    /**
     * POST /api/v1/doctor/appointments/{appointment}/complete
     */
    public function complete(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->doctor_id !== $request->user()->doctor->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($appointment->status !== AppointmentStatus::Confirmed) {
            return response()->json(['message' => 'Solo se pueden completar citas confirmadas.'], 422);
        }

        $data = $request->validate([
            'doctor_notes' => 'nullable|string|max:2000',
        ]);

        $appointment->update([
            'status'       => AppointmentStatus::Completed,
            'doctor_notes' => $data['doctor_notes'] ?? null,
        ]);

        return response()->json(['message' => 'Cita completada.', 'appointment' => $appointment->fresh()]);
    }

    /**
     * GET /api/v1/doctor/appointments/stats
     * Estadísticas para el dashboard del doctor.
     */
    public function stats(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor;
        $today  = now()->toDateString();

        return response()->json([
            'today'     => $doctor->appointments()->whereDate('starts_at', $today)->count(),
            'pending'   => $doctor->appointments()->where('status', AppointmentStatus::Pending)->count(),
            'confirmed' => $doctor->appointments()->where('status', AppointmentStatus::Confirmed)->count(),
            'completed' => $doctor->appointments()->where('status', AppointmentStatus::Completed)->count(),
            'patients'  => $doctor->appointments()->distinct('patient_id')->count('patient_id'),
        ]);
    }
}
