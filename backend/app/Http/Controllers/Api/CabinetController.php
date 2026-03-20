<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CabinetController extends Controller
{
    /**
     * List all cabinets with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Cabinet::query();

        if ($request->filled('bts_site')) {
            $query->where('bts_site', $request->bts_site);
        }

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereRaw('LOWER(cabinet_code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(bts_site) LIKE ?', ["%{$search}%"]);
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $cabinets = $query->orderBy('cabinet_code')->paginate($perPage);

        return response()->json($cabinets);
    }

    /**
     * Get single cabinet.
     */
    public function show(string $cabinetCode): JsonResponse
    {
        $cabinet = Cabinet::findOrFail($cabinetCode);

        return response()->json([
            'data' => $cabinet,
        ]);
    }

    /**
     * Store new cabinet.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cabinet_code' => 'required|string|unique:cabinets,cabinet_code',
            'bts_site' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'note' => 'nullable|string',
        ]);

        $cabinet = Cabinet::create($validated);

        return response()->json([
            'data' => $cabinet,
            'message' => 'Cabinet created successfully',
        ], 201);
    }

    /**
     * Update cabinet.
     */
    public function update(Request $request, string $cabinetCode): JsonResponse
    {
        $cabinet = Cabinet::findOrFail($cabinetCode);

        $validated = $request->validate([
            'bts_site' => 'sometimes|string',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
            'note' => 'nullable|string',
        ]);

        $cabinet->update($validated);

        return response()->json([
            'data' => $cabinet->fresh(),
            'message' => 'Cabinet updated successfully',
        ]);
    }

    /**
     * Delete cabinet.
     */
    public function destroy(string $cabinetCode): JsonResponse
    {
        $cabinet = Cabinet::findOrFail($cabinetCode);
        $cabinet->delete();

        return response()->json([
            'message' => 'Cabinet deleted successfully',
        ]);
    }

    /**
     * Download template Excel file.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['cabinet_code', 'bts_site', 'lat', 'lng', 'note'],
            ['CAB001', 'BTS_SITE_01', '10.776889', '106.701755', 'Tủ cáp khu vực 1'],
            ['CAB002', 'BTS_SITE_02', '10.782345', '106.689123', 'Tủ cáp khu vực 2'],
        ]);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'cabinet_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export cabinets to CSV.
     */
    public function export(): StreamedResponse
    {
        $cabinets = Cabinet::query()
            ->orderBy('cabinet_code')
            ->get(['cabinet_code', 'bts_site', 'lat', 'lng', 'note', 'created_at', 'updated_at']);

        return response()->streamDownload(function () use ($cabinets) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['cabinet_code', 'bts_site', 'lat', 'lng', 'note', 'created_at', 'updated_at']);

            foreach ($cabinets as $cabinet) {
                fputcsv($handle, [
                    $cabinet->cabinet_code,
                    $cabinet->bts_site,
                    $cabinet->lat,
                    $cabinet->lng,
                    $cabinet->note,
                    optional($cabinet->created_at)->toISOString(),
                    optional($cabinet->updated_at)->toISOString(),
                ]);
            }

            fclose($handle);
        }, 'cabinets.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Import cabinets from CSV/Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            $rows = array_map('str_getcsv', file($file->getRealPath()));
        } else {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray();
        }

        array_shift($rows);

        $imported = 0;
        $failed = 0;
        $errors = [];
        $results = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            $cabinetCode = trim((string) ($row[0] ?? ''));
            $btsSite = trim((string) ($row[1] ?? ''));
            $lat = trim((string) ($row[2] ?? ''));
            $lng = trim((string) ($row[3] ?? ''));
            $note = trim((string) ($row[4] ?? ''));

            if ($cabinetCode === '') {
                $failed++;
                $errors[] = "Row {$rowNum}: cabinet_code is required";
                $results[] = $this->makeImportResultRow($cabinetCode, $btsSite, 'failed', 'cabinet_code is required');
                continue;
            }

            if ($btsSite === '') {
                $failed++;
                $errors[] = "Row {$rowNum}: bts_site is required";
                $results[] = $this->makeImportResultRow($cabinetCode, $btsSite, 'failed', 'bts_site is required');
                continue;
            }

            if (!is_numeric($lat) || !is_numeric($lng)) {
                $failed++;
                $errors[] = "Row {$rowNum}: lat/lng must be numeric";
                $results[] = $this->makeImportResultRow($cabinetCode, $btsSite, 'failed', 'lat/lng must be numeric');
                continue;
            }

            try {
                Cabinet::updateOrCreate(
                    ['cabinet_code' => $cabinetCode],
                    [
                        'bts_site' => $btsSite,
                        'lat' => (float) $lat,
                        'lng' => (float) $lng,
                        'note' => $note !== '' ? $note : null,
                    ]
                );

                $imported++;
                $results[] = $this->makeImportResultRow($cabinetCode, $btsSite, 'success');
            } catch (\Throwable $exception) {
                $failed++;
                $errors[] = "Row {$rowNum}: {$exception->getMessage()}";
                $results[] = $this->makeImportResultRow($cabinetCode, $btsSite, 'failed', $exception->getMessage());
            }
        }

        $exportToken = Str::uuid()->toString();
        Cache::put($this->importResultsCacheKey($exportToken), $results, now()->addHour());

        return response()->json([
            'message' => "Import completed: {$imported} success, {$failed} failed",
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
            'results' => $results,
            'export_token' => $exportToken,
        ]);
    }

    /**
     * Get all cabinets for map view.
     */
    public function map(): JsonResponse
    {
        $cabinets = Cabinet::select(['cabinet_code', 'bts_site', 'lat', 'lng', 'note'])->get();

        return response()->json([
            'data' => $cabinets,
        ]);
    }

    /**
     * Export import results to CSV by token.
     */
    public function exportResult(Request $request): StreamedResponse|JsonResponse
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'message' => 'Missing export token.',
            ], 422);
        }

        $results = Cache::get($this->importResultsCacheKey($token));

        if (!is_array($results)) {
            return response()->json([
                'message' => 'Import result not found or has expired.',
            ], 404);
        }

        return response()->streamDownload(function () use ($results) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['cabinet_code', 'bts_site', 'status', 'error']);

            foreach ($results as $row) {
                fputcsv($handle, [
                    $row['cabinet_code'] ?? '',
                    $row['bts_site'] ?? '',
                    $row['status'] ?? '',
                    $row['error'] ?? '',
                ]);
            }

            fclose($handle);
        }, 'import_results.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function importResultsCacheKey(string $token): string
    {
        return "cabinet_import_results:{$token}";
    }

    private function makeImportResultRow(string $cabinetCode, string $btsSite, string $status, ?string $error = null): array
    {
        return [
            'cabinet_code' => $cabinetCode,
            'bts_site' => $btsSite,
            'status' => $status,
            'error' => $error,
        ];
    }
}
