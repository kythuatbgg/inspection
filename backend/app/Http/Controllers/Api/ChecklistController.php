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
        $checklists = Checklist::withCount(['items', 'batches' => function ($q) {
                $q->where('status', '!=', 'completed');
            }])
            ->orderBy('id')
            ->get()
            ->map(function ($c) {
                $c->has_active_batch = $c->batches_count > 0;
                return $c;
            });

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

        $hasActiveBatch = $this->hasActiveBatch($checklistId);

        $lang = $request->user()->lang_pref ?? 'vn';

        $items = $checklist->items->map(function ($item) use ($lang) {
            return [
                'id' => $item->id,
                'category' => $item->category,
                'category_vn' => $item->category_vn,
                'category_en' => $item->category_en,
                'category_kh' => $item->category_kh,
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
                'has_active_batch' => $hasActiveBatch,
                'created_at' => $checklist->created_at,
                'updated_at' => $checklist->updated_at,
            ],
        ]);
    }

    /**
     * Get items for a checklist
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
     * Store new checklist
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
            'message' => __('messages.checklist_create_success'),
        ], 201);
    }

    /**
     * Update checklist
     */
    public function update(Request $request, int $checklistId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);

        if ($this->hasActiveBatch($checklistId)) {
            return response()->json([
                'message' => __('messages.checklist_in_use_edit'),
                'has_active_batch' => true,
            ], 422);
        }

        $request->validate([
            'name' => 'sometimes|required|string',
            'min_pass_score' => 'sometimes|integer|min:0|max:100',
            'max_critical_allowed' => 'sometimes|integer|min:0',
        ]);

        $checklist->update($request->only(['name', 'min_pass_score', 'max_critical_allowed']));

        return response()->json([
            'data' => $checklist->fresh(),
            'message' => __('messages.checklist_update_success'),
        ]);
    }

    /**
     * Delete checklist
     */
    public function destroy(int $checklistId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);

        if ($this->hasActiveBatch($checklistId)) {
            return response()->json([
                'message' => __('messages.checklist_in_use_delete'),
                'has_active_batch' => true,
            ], 422);
        }

        $checklist->items()->delete();
        $checklist->delete();

        return response()->json([
            'message' => __('messages.checklist_delete_success'),
        ]);
    }

    /**
     * Clone checklist with all items
     * POST /checklists/{id}/clone
     */
    public function duplicate(int $checklistId): JsonResponse
    {
        $original = Checklist::with('items')->findOrFail($checklistId);

        $newChecklist = Checklist::create([
            'name' => $original->name . ' (Bản sao)',
            'min_pass_score' => $original->min_pass_score,
            'max_critical_allowed' => $original->max_critical_allowed,
        ]);

        foreach ($original->items as $item) {
            ChecklistItem::create([
                'checklist_id' => $newChecklist->id,
                'category' => $item->category,
                'category_vn' => $item->category_vn,
                'category_en' => $item->category_en,
                'category_kh' => $item->category_kh,
                'content_vn' => $item->content_vn,
                'content_en' => $item->content_en,
                'content_kh' => $item->content_kh,
                'max_score' => $item->max_score,
                'is_critical' => $item->is_critical,
            ]);
        }

        return response()->json([
            'data' => $newChecklist->load('items'),
            'message' => __('messages.checklist_clone_success', ['count' => count($original->items)]),
        ], 201);
    }

    // ── Items CRUD ──

    /**
     * Add item to checklist
     * POST /checklists/{id}/items
     */
    public function storeItem(Request $request, int $checklistId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);

        if ($this->hasActiveBatch($checklistId)) {
            return response()->json([
                'message' => __('messages.checklist_in_use_add_item'),
                'has_active_batch' => true,
            ], 422);
        }

        $request->validate([
            'category' => 'required|string',
            'category_vn' => 'nullable|string',
            'category_en' => 'nullable|string',
            'category_kh' => 'nullable|string',
            'content_vn' => 'required|string',
            'content_en' => 'nullable|string',
            'content_kh' => 'nullable|string',
            'max_score' => 'required|integer|min:0',
            'is_critical' => 'required|boolean',
        ]);

        $item = $checklist->items()->create($request->all());

        return response()->json([
            'data' => $item,
            'message' => __('messages.item_add_success'),
        ], 201);
    }

    /**
     * Update item
     * PUT /checklists/{id}/items/{item}
     */
    public function updateItem(Request $request, int $checklistId, int $itemId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);

        if ($this->hasActiveBatch($checklistId)) {
            return response()->json([
                'message' => __('messages.checklist_in_use_edit_item'),
                'has_active_batch' => true,
            ], 422);
        }

        $item = $checklist->items()->where('id', $itemId)->firstOrFail();

        $request->validate([
            'category' => 'sometimes|required|string',
            'category_vn' => 'nullable|string',
            'category_en' => 'nullable|string',
            'category_kh' => 'nullable|string',
            'content_vn' => 'sometimes|required|string',
            'content_en' => 'nullable|string',
            'content_kh' => 'nullable|string',
            'max_score' => 'sometimes|required|integer|min:0',
            'is_critical' => 'sometimes|required|boolean',
        ]);

        $item->update($request->all());

        return response()->json([
            'data' => $item->fresh(),
            'message' => __('messages.item_update_success'),
        ]);
    }

    /**
     * Delete item
     * DELETE /checklists/{id}/items/{item}
     */
    public function destroyItem(int $checklistId, int $itemId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);

        if ($this->hasActiveBatch($checklistId)) {
            return response()->json([
                'message' => __('messages.checklist_in_use_delete_item'),
                'has_active_batch' => true,
            ], 422);
        }

        $item = $checklist->items()->where('id', $itemId)->firstOrFail();
        $item->delete();

        return response()->json([
            'message' => __('messages.item_delete_success'),
        ]);
    }

    // ── Helper ──

    private function hasActiveBatch(int $checklistId): bool
    {
        return \App\Models\InspectionBatch::where('checklist_id', $checklistId)
            ->where('status', '!=', 'completed')
            ->exists();
    }
}
