<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionBatch;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function inspectionReport(Request $request, int $inspectionId)
    {
        $inspection = Inspection::with('planDetail.batch')->findOrFail($inspectionId);

        if ($request->user()->role === 'inspector' && $inspection->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Không có quyền xem báo cáo này.'], 403);
        }

        $data = $this->reportService->getInspectionReportData($inspection);
        $pdf = Pdf::loadView('reports.inspection', $data)->setPaper('a4');

        return $pdf->download("bien-ban-kiem-tra-{$inspection->cabinet_code}.pdf");
    }

    public function batchSummary(Request $request, int $batchId)
    {
        $batch = InspectionBatch::findOrFail($batchId);

        if ($request->user()->role === 'inspector' && $batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Không có quyền xem báo cáo này.'], 403);
        }

        $data = $this->reportService->getBatchSummaryData($batch);
        $pdf = Pdf::loadView('reports.batch-summary', $data)->setPaper('a4', 'landscape');

        return $pdf->download("tong-ket-dot-{$batch->id}.pdf");
    }

    public function batchExport(Request $request, int $batchId): StreamedResponse|JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);

        if ($request->user()->role === 'inspector' && $batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Không có quyền xem báo cáo này.'], 403);
        }

        $data = $this->reportService->getBatchExportData($batch);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kết quả kiểm tra');

        $sheet->fromArray($data['headers'], null, 'A1');

        $rowIndex = 2;
        foreach ($data['rows'] as $row) {
            $sheet->fromArray(array_values($row), null, "A{$rowIndex}");
            $rowIndex++;
        }

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Style header row
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);
        $filename = "thong-ke-dot-{$batch->id}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function criticalErrors(Request $request)
    {
        $filters = $request->only(['batch_id', 'from', 'to']);
        $data = $this->reportService->getCriticalErrorsData($filters);

        $format = $request->query('format', 'pdf');

        if ($format === 'xlsx') {
            return $this->criticalErrorsExcel($data);
        }

        $pdf = Pdf::loadView('reports.critical-errors', $data)->setPaper('a4');
        return $pdf->download('bao-cao-loi-critical.pdf');
    }

    public function acceptance(Request $request, int $batchId)
    {
        $batch = InspectionBatch::findOrFail($batchId);

        if ($request->user()->role === 'inspector' && $batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Không có quyền xem báo cáo này.'], 403);
        }

        $data = $this->reportService->getAcceptanceData($batch);
        $pdf = Pdf::loadView('reports.acceptance', $data)->setPaper('a4');

        return $pdf->download("bien-ban-nghiem-thu-dot-{$batch->id}.pdf");
    }

    private function criticalErrorsExcel(array $data): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lỗi Critical');

        $headers = ['Mã tủ', 'Trạm BTS', 'Đợt', 'Hạng mục', 'Nội dung lỗi', 'Inspector', 'Ngày KT', 'Ghi chú'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $rowIndex = 2;
        foreach ($data['errors'] as $err) {
            $sheet->fromArray([
                $err['cabinet_code'],
                $err['bts_site'],
                $err['batch_name'],
                $err['item_category'],
                $err['item_content'],
                $err['inspector'],
                $err['inspected_at'],
                $err['note'] ?? '',
            ], null, "A{$rowIndex}");
            $rowIndex++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'loi-critical.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
