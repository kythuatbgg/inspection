<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CabinetController extends Controller
{
    /**
     * List all cabinets
     */
    public function index(Request $request): JsonResponse
    {
        $query = Cabinet::query();

        // Filter by BTS site
        if ($request->has('bts_site')) {
            $query->where('bts_site', $request->bts_site);
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cabinet_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $cabinets = $query->orderBy('cabinet_code')->get();

        return response()->json([
            'data' => $cabinets,
        ]);
    }

    /**
     * Get single cabinet
     */
    public function show(string $cabinetCode): JsonResponse
    {
        $cabinet = Cabinet::findOrFail($cabinetCode);

        return response()->json([
            'data' => $cabinet,
        ]);
    }

    /**
     * Store new cabinet (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cabinet_code' => 'required|unique:cabinets,cabinet_code',
            'bts_site' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $cabinet = Cabinet::create($request->all());

        return response()->json([
            'data' => $cabinet,
            'message' => 'Cabinet created successfully',
        ], 201);
    }

    /**
     * Update cabinet (Admin only)
     */
    public function update(Request $request, string $cabinetCode): JsonResponse
    {
        $cabinet = Cabinet::findOrFail($cabinetCode);

        $request->validate([
            'bts_site' => 'sometimes|string',
            'name' => 'sometimes|string',
            'type' => 'sometimes|string',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
        ]);

        $cabinet->update($request->all());

        return response()->json([
            'data' => $cabinet,
            'message' => 'Cabinet updated successfully',
        ]);
    }

    /**
     * Delete cabinet (Admin only)
     */
    public function destroy(string $cabinetCode): JsonResponse
    {
        $cabinet = Cabinet::findOrFail($cabinetCode);
        $cabinet->delete();

        return response()->json([
            'message' => 'Cabinet deleted successfully',
        ]);
    }
}
