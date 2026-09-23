<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorSearchController extends Controller
{
    /**
     * GET /api/v1/doctors
     * Búsqueda pública de terapeutas aprobados con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Doctor::with([
            'user:id,name,email',
            'specialty:id,name,slug',
            'therapeuticApproaches:id,name,slug',
            'therapyModalities:id,name,slug,icon',
            'targetPopulations:id,name,slug',
        ])
        ->where('status', 'approved')
        ->where('license_status', 'verified');

        // Filtro por especialidad
        if ($request->filled('specialty_id')) {
            $query->where('specialty_id', $request->specialty_id);
        }

        // Filtro por ciudad
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        // Filtro por precio máximo
        if ($request->filled('max_price')) {
            $query->where('consultation_price', '<=', $request->max_price);
        }

        // Filtro por precio mínimo
        if ($request->filled('min_price')) {
            $query->where('consultation_price', '>=', $request->min_price);
        }

        // Filtro por enfoque terapéutico
        if ($request->filled('approach_id')) {
            $query->whereHas('therapeuticApproaches', fn($q) =>
                $q->where('therapeutic_approaches.id', $request->approach_id)
            );
        }

        // Filtro por modalidad
        if ($request->filled('modality_id')) {
            $query->whereHas('therapyModalities', fn($q) =>
                $q->where('therapy_modalities.id', $request->modality_id)
            );
        }

        // Filtro por población
        if ($request->filled('population_id')) {
            $query->whereHas('targetPopulations', fn($q) =>
                $q->where('target_populations.id', $request->population_id)
            );
        }

        // Filtro por texto (nombre del terapeuta)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) =>
                $q->where('name', 'like', '%' . $search . '%')
            );
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'rating');
        match($sortBy) {
            'price_asc'  => $query->orderBy('consultation_price', 'asc'),
            'price_desc' => $query->orderBy('consultation_price', 'desc'),
            'experience' => $query->orderBy('experience_years', 'desc'),
            default      => $query->orderBy('rating', 'desc'),
        };

        $doctors = $query->paginate(12);

        return response()->json($doctors);
    }

    /**
     * GET /api/v1/doctors/{doctor}
     * Perfil público de un terapeuta.
     */
    public function show(Doctor $doctor): JsonResponse
    {
        if ($doctor->status !== 'approved' || $doctor->license_status !== 'verified') {
            return response()->json(['message' => 'Terapeuta no disponible.'], 404);
        }

        $doctor->load([
            'user:id,name,email',
            'specialty:id,name,slug',
            'therapeuticApproaches:id,name,slug',
            'therapyModalities:id,name,slug,icon',
            'targetPopulations:id,name,slug',
        ]);

        return response()->json($doctor);
    }

    /**
     * GET /api/v1/doctors/filters
     * Opciones disponibles para los filtros.
     */
    public function filters(): JsonResponse
    {
        $specialties = Specialty::whereHas('doctors', fn($q) =>
            $q->where('status', 'approved')->where('license_status', 'verified')
        )->orderBy('name')->get(['id', 'name', 'slug']);

        $cities = Doctor::where('status', 'approved')
            ->where('license_status', 'verified')
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return response()->json([
            'specialties' => $specialties,
            'cities'      => $cities,
        ]);
    }
}
