<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StorageController extends Controller
{
    /**
     * Get storage statistics for the admin dashboard.
     */
    public function stats(): JsonResponse
    {
        $disk = Storage::disk('public');

        $uploadFiles = $disk->files('images/inspections');
        $usedFiles = $this->getUsedUploadFilenames();

        $orphanCount = 0;
        $orphanSize = 0;
        $usedUploadCount = 0;
        $usedUploadSize = 0;

        foreach ($uploadFiles as $file) {
            $size = $disk->size($file);
            $filename = basename($file);

            if ($usedFiles->contains($filename)) {
                $usedUploadCount++;
                $usedUploadSize += $size;
                continue;
            }

            $orphanCount++;
            $orphanSize += $size;
        }

        // Spatie media
        $mediaCount = Media::count();
        $mediaSizeBytes = Media::sum('size');

        return response()->json([
            'orphan_files_count' => $orphanCount,
            'orphan_files_size_mb' => round($orphanSize / 1024 / 1024, 2),
            'used_upload_files_count' => $usedUploadCount,
            'used_upload_files_size_mb' => round($usedUploadSize / 1024 / 1024, 2),
            'upload_files_count' => count($uploadFiles),
            'upload_files_size_mb' => round(($orphanSize + $usedUploadSize) / 1024 / 1024, 2),
            'media_count' => $mediaCount,
            'media_size_mb' => round($mediaSizeBytes / 1024 / 1024, 2),
            'total_size_mb' => round(($orphanSize + $usedUploadSize + $mediaSizeBytes) / 1024 / 1024, 2),
        ]);
    }

    /**
     * Manually trigger cleanup of orphan temp files.
     */
    public function cleanup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hours' => 'nullable|integer|in:0,1,6,24,72,168',
        ]);

        $hours = $validated['hours'] ?? 24;

        Artisan::call('uploads:cleanup', ['--hours' => $hours]);
        $output = Artisan::output();

        return response()->json([
            'message' => trim($output),
        ]);
    }

    private function getUsedUploadFilenames(): Collection
    {
        $usedFiles = collect();

        InspectionDetail::query()
            ->whereNotNull('image_url')
            ->pluck('image_url')
            ->each(function (?string $url) use ($usedFiles) {
                if ($url) {
                    $usedFiles->push(basename($url));
                }
            });

        Inspection::query()
            ->whereNotNull('overall_photos')
            ->get(['overall_photos'])
            ->each(function (Inspection $inspection) use ($usedFiles) {
                $photos = $inspection->getAttributes()['overall_photos'] ?? '[]';
                $photoList = is_string($photos) ? json_decode($photos, true) : $photos;

                if (!is_array($photoList)) {
                    return;
                }

                foreach ($photoList as $photoUrl) {
                    if ($photoUrl) {
                        $usedFiles->push(basename($photoUrl));
                    }
                }
            });

        return $usedFiles->filter()->unique()->values();
    }
}
