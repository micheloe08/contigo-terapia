<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Enums\AppointmentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * GET /api/v1/patient/appointments
     * Lista las citas del paciente autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'all');

        $query = $request->user()->patient
            ->appointments()
            ->with(['doctor.user:id,name', 'doctor.specialty:id,name'])
            ->orderBy('starts_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(10));
    }

    /**
     * POST /api/v1/patient/appointments
     * Agenda una nueva cita.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'doctor_id'  => 'required|exists:doctors,id',
            'starts_at'  => 'required|date|after:now',
            'type'       => 'required|in:presencial,videollamada,chat',
            'reason'     => 'nullable|string|max:500',
        ]);

        $doctor  = Doctor::findOrFail($data['doctor_id']);
        $patient = $request->user()->patient;

        // Verificar que el doctor está aprobado
        if ($doctor->status !== 'approved') {
            return response()->json(['message' => 'Este terapeuta no está disponible.'], 422);
        }

        $startsAt = \Carbon\Carbon::parse($data['starts_at']);
        $endsAt   = $startsAt->copy()->addMinutes($doctor->session_duration ?? 60);

        // Verificar disponibilidad
        $conflict = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->whereBetween('starts_at', [$startsAt, $endsAt])
                  ->orWhereBetween('ends_at', [$startsAt, $endsAt])
                  ->orWhere(function ($q2) use ($startsAt, $endsAt) {
                      $q2->where('starts_at', '<=', $startsAt)
                         ->where('ends_at', '>=', $endsAt);
                  });
            })->exists();

        if ($conflict) {
            return response()->json(['message' => 'El terapeuta ya tiene una cita en ese horario.'], 422);
        }

        $appointment = Appointment::create([
            'doctor_id'  => $doctor->id,
            'patient_id' => $patient->id,
            'starts_at'  => $startsAt,
            'ends_at'    => $endsAt,
            'type'       => $data['type'],
            'status'     => AppointmentStatus::Pending,
            'reason'     => $data['reason'] ?? null,
            'price'      => $doctor->consultation_price,
            'currency'   => $doctor->currency ?? 'MXN',
        ]);

        return response()->json(
            $appointment->load(['doctor.user:id,name', 'doctor.specialty:id,name']),
            201
        );
    }

    /**
     * GET /api/v1/patient/appointments/{appointment}
     */
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->patient_id !== $request->user()->patient->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $appointment->load(['doctor.user:id,name', 'doctor.specialty:id,name']);

        return response()->json($appointment);
    }

    /**
     * POST /api/v1/patient/appointments/{appointment}/cancel
     */
    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->patient_id !== $request->user()->patient->id) {
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
}
