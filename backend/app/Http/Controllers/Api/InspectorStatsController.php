<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspectorStatsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $query = Inspection::where('user_id', $userId);

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $inspections = $query->get();
        $total = $inspections->count();
        $passed = $inspections->where('final_result', 'PASS')->count();
        $failed = $inspections->where('final_result', 'FAIL')->count();

        return response()->json([
            'data' => [
                'total' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
                'critical_errors' => $inspections->sum('critical_errors_count'),
                'avg_score' => round($inspections->avg('total_score') ?? 0, 1),
            ],
        ]);
    }

    public function timeline(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('Y-m'));
        }

        $query = Inspection::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth());

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $inspections = $query->get();

        $data = $months->map(function ($month) use ($inspections) {
            $monthInspections = $inspections->filter(
                fn($i) => $i->created_at->format('Y-m') === $month
            );

            return [
                'month' => $month,
                'total' => $monthInspections->count(),
                'passed' => $monthInspections->where('final_result', 'PASS')->count(),
                'failed' => $monthInspections->where('final_result', 'FAIL')->count(),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function topErrors(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $query = InspectionDetail::where('is_failed', true)
            ->whereHas('item', fn($q) => $q->where('is_critical', true))
            ->whereHas('inspection', fn($q) => $q->where('user_id', $userId))
            ->with('item:id,category,content_vn,content_en');

        if ($request->filled('from')) {
            $query->whereHas('inspection', fn($q) => $q->where('created_at', '>=', $request->from));
        }
        if ($request->filled('to')) {
            $query->whereHas('inspection', fn($q) => $q->where('created_at', '<=', $request->to . ' 23:59:59'));
        }

        $details = $query->get();

        $data = $details->groupBy(fn($d) => $d->item->content_vn ?? 'Unknown')
            ->map(fn($items, $content) => [
                'error_content' => $content,
                'category' => $items->first()->item->category ?? '',
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->take(10);

        return response()->json(['data' => $data]);
    }

    public function inspections(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $query = Inspection::where('user_id', $userId)
            ->with(['planDetail.batch:id,name']);

        if ($request->filled('search')) {
            $query->where('cabinet_code', 'like', "%{$request->search}%");
        }
        if ($request->filled('batch_id')) {
            $query->whereHas('planDetail', fn($q) => $q->where('batch_id', $request->batch_id));
        }
        if ($request->filled('result')) {
            $query->where('final_result', $request->result);
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $inspections = $query->orderByDesc('created_at')->get();

        $data = $inspections->map(fn($i) => [
            'id' => $i->id,
            'cabinet_code' => $i->cabinet_code,
            'batch_name' => $i->planDetail?->batch?->name ?? '—',
            'final_result' => $i->final_result,
            'total_score' => $i->total_score,
            'critical_errors_count' => $i->critical_errors_count,
            'created_at' => $i->created_at->toIso8601String(),
        ]);

        return response()->json(['data' => $data]);
    }
}
