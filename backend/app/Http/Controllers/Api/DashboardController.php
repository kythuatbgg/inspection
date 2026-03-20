<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InspectionBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $query = InspectionBatch::query();

        if ($request->user()->role === 'inspector') {
            $query->where('user_id', $request->user()->id);
        }

        $total = (clone $query)->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        $failed = (clone $query)->where('status', 'failed')->count();

        return response()->json([
            'total_batches' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'failed' => $failed,
        ]);
    }
}
