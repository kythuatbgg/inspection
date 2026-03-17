<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChecklistController extends Controller
{
    /**
     * List all checklists
     */
    public function index(Request $request): JsonResponse
    {
        $checklists = Checklist::withCount('items')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $checklists,
        ]);
    }

    /**
     * Get single checklist with items
     */
    public function show(Request $request, int $checklistId): JsonResponse
    {
        $checklist = Checklist::with('items')->findOrFail($checklistId);

        $lang = $request->user()->lang_pref ?? 'vn';

        // Transform items to include content based on language preference
        $items = $checklist->items->map(function ($item) use ($lang) {
            return [
                'id' => $item->id,
                'category' => $item->category,
                'content' => $item->{"content_{$lang}"},
                'content_vn' => $item->content_vn,
                'content_en' => $item->content_en,
                'content_kh' => $item->content_kh,
                'max_score' => $item->max_score,
                'is_critical' => $item->is_critical,
            ];
        });

        return response()->json([
            'data' => [
                'id' => $checklist->id,
                'name' => $checklist->name,
                'min_pass_score' => $checklist->min_pass_score,
                'max_critical_allowed' => $checklist->max_critical_allowed,
                'items' => $items,
            ],
        ]);
    }

    /**
     * Get items for a checklist (alternative endpoint)
     */
    public function items(int $checklistId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);
        $items = $checklist->items()->orderBy('id')->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Store new checklist (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'min_pass_score' => 'sometimes|integer|min:0|max:100',
            'max_critical_allowed' => 'sometimes|integer|min:0',
        ]);

        $checklist = Checklist::create($request->all());

        return response()->json([
            'data' => $checklist,
            'message' => 'Checklist created successfully',
        ], 201);
    }
}
