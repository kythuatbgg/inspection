<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use App\Models\Inspection;
use App\Models\InspectionBatch;
use App\Models\InspectionDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $query = Inspection::query();

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
        $criticalErrors = $inspections->sum('critical_errors_count');

        $batchQuery = InspectionBatch::query();
        if ($request->filled('from')) {
            $batchQuery->where('start_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $batchQuery->where('end_date', '<=', $request->to);
        }

        return response()->json([
            'data' => [
                'total_batches' => $batchQuery->count(),
                'total_cabinets_inspected' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
                'fail_rate' => $total > 0 ? round(($failed / $total) * 100, 1) : 0,
                'total_critical_errors' => $criticalErrors,
                'avg_score' => round($inspections->avg('total_score') ?? 0, 1),
            ],
        ]);
    }

    public function byBts(Request $request): JsonResponse
    {
        $query = Inspection::with('cabinet:cabinet_code,bts_site');

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $inspections = $query->get();

        $grouped = $inspections->groupBy(fn($i) => $i->cabinet->bts_site ?? 'Unknown');

        $data = $grouped->map(function ($items, $bts) {
            $total = $items->count();
            $passed = $items->where('final_result', 'PASS')->count();
            $failed = $items->where('final_result', 'FAIL')->count();

            return [
                'bts_site' => $bts,
                'total' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
                'avg_score' => round($items->avg('total_score') ?? 0, 1),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function byInspector(Request $request): JsonResponse
    {
        $query = Inspection::with('user:id,name,username');

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $inspections = $query->get();

        $grouped = $inspections->groupBy('user_id');

        $data = $grouped->map(function ($items) {
            $total = $items->count();
            $passed = $items->where('final_result', 'PASS')->count();
            $user = $items->first()->user;

            return [
                'inspector_id' => $user->id ?? null,
                'inspector_name' => $user->name ?? 'Unknown',
                'total_inspections' => $total,
                'passed' => $passed,
                'failed' => $total - $passed,
                'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
                'avg_score' => round($items->avg('total_score') ?? 0, 1),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function byErrorType(Request $request): JsonResponse
    {
        $query = InspectionDetail::where('is_failed', true)
            ->whereHas('item', fn($q) => $q->where('is_critical', true))
            ->with('item:id,category,content_vn,content_en');

        if ($request->filled('batch_id')) {
            $query->whereHas('inspection.planDetail', fn($q) => $q->where('batch_id', $request->batch_id));
        }
        if ($request->filled('from')) {
            $query->whereHas('inspection', fn($q) => $q->where('created_at', '>=', $request->from));
        }
        if ($request->filled('to')) {
            $query->whereHas('inspection', fn($q) => $q->where('created_at', '<=', $request->to . ' 23:59:59'));
        }

        $details = $query->get();

        $grouped = $details->groupBy(fn($d) => $d->item->content_vn ?? 'Unknown');

        $data = $grouped->map(function ($items, $content) {
            return [
                'error_content' => $content,
                'category' => $items->first()->item->category ?? '',
                'count' => $items->count(),
            ];
        })->sortByDesc('count')->values();

        return response()->json(['data' => $data]);
    }

    public function export(Request $request): StreamedResponse
    {
        $overviewData = $this->getOverviewData($request);
        $btsData = $this->getBtsData($request);
        $inspectorData = $this->getInspectorData($request);

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Overview
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Tổng quan');
        $sheet1->fromArray(['Chỉ số', 'Giá trị'], null, 'A1');
        $row = 2;
        foreach ($overviewData as $key => $value) {
            $sheet1->setCellValue("A{$row}", $key);
            $sheet1->setCellValue("B{$row}", $value);
            $row++;
        }
        $sheet1->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet1->getColumnDimension('A')->setAutoSize(true);
        $sheet1->getColumnDimension('B')->setAutoSize(true);

        // Sheet 2: By BTS
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Theo BTS');
        $sheet2->fromArray(['Trạm BTS', 'Tổng', 'Đạt', 'Không đạt', 'Tỷ lệ đạt (%)', 'Điểm TB'], null, 'A1');
        $row = 2;
        foreach ($btsData as $bts) {
            $sheet2->fromArray(array_values($bts), null, "A{$row}");
            $row++;
        }
        $sheet2->getStyle('A1:F1')->getFont()->setBold(true);

        // Sheet 3: By Inspector
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Theo Inspector');
        $sheet3->fromArray(['Inspector', 'Tổng KT', 'Đạt', 'Không đạt', 'Tỷ lệ đạt (%)', 'Điểm TB'], null, 'A1');
        $row = 2;
        foreach ($inspectorData as $ins) {
            $sheet3->fromArray([
                $ins['inspector_name'],
                $ins['total_inspections'],
                $ins['passed'],
                $ins['failed'],
                $ins['pass_rate'],
                $ins['avg_score'],
            ], null, "A{$row}");
            $row++;
        }
        $sheet3->getStyle('A1:F1')->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'thong-ke-tong-hop.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function getOverviewData(Request $request): array
    {
        $query = Inspection::query();
        if ($request->filled('from')) $query->where('created_at', '>=', $request->from);
        if ($request->filled('to')) $query->where('created_at', '<=', $request->to . ' 23:59:59');

        $inspections = $query->get();
        $total = $inspections->count();
        $passed = $inspections->where('final_result', 'PASS')->count();

        return [
            'Tổng batch' => InspectionBatch::count(),
            'Tổng tủ đã kiểm tra' => $total,
            'Đạt' => $passed,
            'Không đạt' => $total - $passed,
            'Tỷ lệ đạt (%)' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
            'Tổng lỗi Critical' => $inspections->sum('critical_errors_count'),
            'Điểm trung bình' => round($inspections->avg('total_score') ?? 0, 1),
        ];
    }

    private function getBtsData(Request $request): array
    {
        $query = Inspection::with('cabinet:cabinet_code,bts_site');
        if ($request->filled('from')) $query->where('created_at', '>=', $request->from);
        if ($request->filled('to')) $query->where('created_at', '<=', $request->to . ' 23:59:59');

        return $query->get()
            ->groupBy(fn($i) => $i->cabinet->bts_site ?? 'Unknown')
            ->map(function ($items, $bts) {
                $total = $items->count();
                $passed = $items->where('final_result', 'PASS')->count();
                return [
                    'bts_site' => $bts,
                    'total' => $total,
                    'passed' => $passed,
                    'failed' => $total - $passed,
                    'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
                    'avg_score' => round($items->avg('total_score') ?? 0, 1),
                ];
            })->values()->toArray();
    }

    private function getInspectorData(Request $request): array
    {
        $query = Inspection::with('user:id,name');
        if ($request->filled('from')) $query->where('created_at', '>=', $request->from);
        if ($request->filled('to')) $query->where('created_at', '<=', $request->to . ' 23:59:59');

        return $query->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                $total = $items->count();
                $passed = $items->where('final_result', 'PASS')->count();
                return [
                    'inspector_name' => $items->first()->user->name ?? 'Unknown',
                    'total_inspections' => $total,
                    'passed' => $passed,
                    'failed' => $total - $passed,
                    'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
                    'avg_score' => round($items->avg('total_score') ?? 0, 1),
                ];
            })->values()->toArray();
    }
}
