<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * GET /api/v1/doctor/schedules
     * Devuelve todos los horarios del doctor autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $schedules = $request->user()->doctor
            ->schedules()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json($schedules);
    }

    /**
     * POST /api/v1/doctor/schedules
     * Crea o actualiza los horarios del doctor (reemplaza todo el bloque del día).
     */
    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'schedules'                => 'required|array',
            'schedules.*.day_of_week'  => 'required|integer|between:0,6',
            'schedules.*.start_time'   => 'required|date_format:H:i',
            'schedules.*.end_time'     => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.is_available' => 'boolean',
        ]);

        $doctor = $request->user()->doctor;

        // Agrupa por día para reemplazar solo los días que vienen en el payload
        $days = collect($data['schedules'])->pluck('day_of_week')->unique();

        foreach ($days as $day) {
            $doctor->schedules()->where('day_of_week', $day)->delete();
        }

        // Inserta los nuevos
        $schedules = collect($data['schedules'])->map(fn($s) => array_merge($s, [
            'doctor_id'    => $doctor->id,
            'is_available' => $s['is_available'] ?? true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]));

        Schedule::insert($schedules->toArray());

        return response()->json([
            'message'   => 'Horarios actualizados correctamente.',
            'schedules' => $doctor->schedules()->orderBy('day_of_week')->orderBy('start_time')->get(),
        ]);
    }

    /**
     * DELETE /api/v1/doctor/schedules/{schedule}
     * Elimina un bloque de horario específico.
     */
    public function destroy(Request $request, Schedule $schedule): JsonResponse
    {
        if ($schedule->doctor_id !== $request->user()->doctor->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $schedule->delete();

        return response()->json(['message' => 'Horario eliminado.']);
    }

    /**
     * POST /api/v1/doctor/schedules/block
     * Bloquea un día específico (vacaciones, ausencia, etc.).
     */
    public function block(Request $request): JsonResponse
    {
        $data = $request->validate([
            'blocked_date' => 'required|date|after_or_equal:today',
            'block_reason' => 'nullable|string|max:200',
        ]);

        $doctor = $request->user()->doctor;

        Schedule::updateOrCreate(
            ['doctor_id' => $doctor->id, 'blocked_date' => $data['blocked_date']],
            array_merge($data, [
                'doctor_id'    => $doctor->id,
                'day_of_week'  => 0,
                'start_time'   => '00:00',
                'end_time'     => '23:59',
                'is_available' => false,
            ])
        );

        return response()->json(['message' => 'Día bloqueado correctamente.']);
    }
}
