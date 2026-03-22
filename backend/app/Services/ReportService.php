<?php

namespace App\Services;

use App\Models\Cabinet;
use App\Models\Inspection;
use App\Models\InspectionBatch;
use App\Models\InspectionDetail;
use Illuminate\Support\Collection;

class ReportService
{
    public function getInspectionReportData(Inspection $inspection): array
    {
        $inspection->load([
            'user:id,name,username',
            'checklist:id,name,min_pass_score,max_critical_allowed',
            'details.item',
            'cabinet',
        ]);

        $details = $inspection->details->map(fn(InspectionDetail $d) => [
            'category' => $d->item->category ?? $d->item->category_vn ?? '',
            'content' => $d->item->content_vn ?? '',
            'max_score' => $d->item->max_score,
            'score_awarded' => $d->score_awarded,
            'is_failed' => $d->is_failed,
            'is_critical' => $d->item->is_critical,
            'note' => $d->note,
            'image_url' => $d->image_url,
        ]);

        $groupedByCategory = $details->groupBy('category');

        return [
            'inspection' => $inspection,
            'cabinet' => $inspection->cabinet,
            'user' => $inspection->user,
            'checklist' => $inspection->checklist,
            'details' => $details,
            'grouped' => $groupedByCategory,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }

    public function getBatchSummaryData(InspectionBatch $batch): array
    {
        $batch->load([
            'user:id,name,username',
            'checklist:id,name,min_pass_score,max_critical_allowed',
            'planDetails.inspection.details',
            'planDetails.cabinet',
        ]);

        $plans = $batch->planDetails;
        $total = $plans->count();
        $inspected = $plans->filter(fn($p) => $p->inspection !== null)->count();

        $results = $plans->map(function ($plan) {
            $ins = $plan->inspection;
            return [
                'cabinet_code' => $plan->cabinet_code,
                'bts_site' => $plan->cabinet->bts_site ?? '-',
                'status' => $plan->status,
                'review_status' => $plan->review_status ?? 'pending',
                'final_result' => $ins?->final_result,
                'total_score' => $ins?->total_score,
                'critical_errors' => $ins?->critical_errors_count ?? 0,
                'inspected_at' => $ins?->created_at?->format('d/m/Y'),
            ];
        });

        $passed = $results->where('final_result', 'PASS')->count();
        $failed = $results->where('final_result', 'FAIL')->count();
        $avgScore = $results->whereNotNull('total_score')->avg('total_score');

        return [
            'batch' => $batch,
            'user' => $batch->user,
            'checklist' => $batch->checklist,
            'results' => $results,
            'summary' => [
                'total' => $total,
                'inspected' => $inspected,
                'passed' => $passed,
                'failed' => $failed,
                'pass_rate' => $inspected > 0 ? round(($passed / $inspected) * 100, 1) : 0,
                'avg_score' => round($avgScore ?? 0, 1),
            ],
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }

    public function getBatchExportData(InspectionBatch $batch): array
    {
        $batch->load([
            'user:id,name,username',
            'checklist:id,name',
            'planDetails.inspection.details.item',
            'planDetails.cabinet',
        ]);

        $rows = [];
        foreach ($batch->planDetails as $plan) {
            $ins = $plan->inspection;
            $rows[] = [
                'cabinet_code' => $plan->cabinet_code,
                'bts_site' => $plan->cabinet->bts_site ?? '',
                'lat' => $plan->cabinet->lat ?? '',
                'lng' => $plan->cabinet->lng ?? '',
                'status' => $plan->status,
                'review_status' => $plan->review_status ?? 'pending',
                'final_result' => $ins?->final_result ?? 'N/A',
                'total_score' => $ins?->total_score ?? '',
                'critical_errors' => $ins?->critical_errors_count ?? '',
                'inspected_at' => $ins?->created_at?->toISOString() ?? '',
                'inspector' => $batch->user->name ?? '',
            ];
        }

        return [
            'batch' => $batch,
            'headers' => [
                'Mã tủ', 'Trạm BTS', 'Lat', 'Lng', 'Trạng thái',
                'Duyệt', 'Kết quả', 'Điểm', 'Lỗi Critical', 'Ngày KT', 'Inspector',
            ],
            'rows' => $rows,
        ];
    }

    public function getCriticalErrorsData(array $filters): array
    {
        $query = InspectionDetail::whereHas('item', fn($q) => $q->where('is_critical', true))
            ->where('is_failed', true)
            ->with([
                'inspection.user:id,name',
                'inspection.cabinet',
                'inspection.planDetail.batch:id,name',
                'item',
            ]);

        if (!empty($filters['batch_id'])) {
            $query->whereHas('inspection.planDetail', fn($q) => $q->where('batch_id', $filters['batch_id']));
        }

        if (!empty($filters['from'])) {
            $query->whereHas('inspection', fn($q) => $q->where('created_at', '>=', $filters['from']));
        }

        if (!empty($filters['to'])) {
            $query->whereHas('inspection', fn($q) => $q->where('created_at', '<=', $filters['to']));
        }

        $errors = $query->get()->map(fn($d) => [
            'cabinet_code' => $d->inspection->cabinet_code ?? '',
            'bts_site' => $d->inspection->cabinet->bts_site ?? '',
            'batch_name' => $d->inspection->planDetail?->batch?->name ?? '',
            'item_content' => $d->item->content_vn ?? '',
            'item_category' => $d->item->category ?? $d->item->category_vn ?? '',
            'inspector' => $d->inspection->user->name ?? '',
            'inspected_at' => $d->inspection->created_at?->format('d/m/Y'),
            'note' => $d->note,
            'image_url' => $d->image_url,
        ]);

        return [
            'errors' => $errors,
            'total_count' => $errors->count(),
            'by_category' => $errors->groupBy('item_category'),
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }

    public function getAcceptanceData(InspectionBatch $batch): array
    {
        $summaryData = $this->getBatchSummaryData($batch);

        $approved = $batch->planDetails->where('review_status', 'approved')->count();
        $rejected = $batch->planDetails->where('review_status', 'rejected')->count();

        return array_merge($summaryData, [
            'acceptance' => [
                'approved' => $approved,
                'rejected' => $rejected,
                'total_reviewed' => $approved + $rejected,
            ],
        ]);
    }
}
