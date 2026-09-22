<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TherapeuticApproach;
use App\Models\TherapyModality;
use App\Models\TargetPopulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    // ─── Helpers ────────────────────────────────────────────────────────────

    private function model(string $type): string
    {
        return match($type) {
            'approaches'  => TherapeuticApproach::class,
            'modalities'  => TherapyModality::class,
            'populations' => TargetPopulation::class,
            default       => abort(404, 'Catálogo no encontrado.'),
        };
    }

    // ─── CRUD genérico ───────────────────────────────────────────────────────

    /**
     * GET /api/v1/admin/catalogs/{type}
     */
    public function index(string $type): JsonResponse
    {
        $model = $this->model($type);
        $items = $model::orderBy('name')->get();
        return response()->json($items);
    }

    /**
     * POST /api/v1/admin/catalogs/{type}
     */
    public function store(Request $request, string $type): JsonResponse
    {
        $this->model($type);

        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:50',
            'is_active'   => 'boolean',
        ]);

        $data['slug']      = Str::slug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $model = $this->model($type);
        $item  = $model::create($data);

        return response()->json($item, 201);
    }

    /**
     * PUT /api/v1/admin/catalogs/{type}/{id}
     */
    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->model($type);
        $item  = $model::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:150',
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:50',
            'is_active'   => 'boolean',
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $item->update($data);

        return response()->json($item);
    }

    /**
     * DELETE /api/v1/admin/catalogs/{type}/{id}
     */
    public function destroy(string $type, int $id): JsonResponse
    {
        $model = $this->model($type);
        $item  = $model::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Eliminado correctamente.']);
    }

    /**
     * GET /api/v1/catalogs
     * Endpoint público — devuelve los 3 catálogos activos de una vez.
     */
    public function public(): JsonResponse
    {
        return response()->json([
            'approaches'  => TherapeuticApproach::where('is_active', true)->orderBy('name')->get(),
            'modalities'  => TherapyModality::where('is_active', true)->orderBy('name')->get(),
            'populations' => TargetPopulation::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
