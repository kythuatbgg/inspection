<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOrphanUploads extends Command
{
    protected $signature = 'uploads:cleanup {--hours=24 : Hours threshold for orphan files}';
    protected $description = 'Remove orphan temp upload files older than specified hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $threshold = Carbon::now()->subHours($hours);
        $disk = Storage::disk('public');
        $files = $disk->files('images/inspections');

        // Compile list of actively used files
        $usedFiles = collect();

        // 1. From InspectionDetail
        $detailImages = \App\Models\InspectionDetail::whereNotNull('image_url')->pluck('image_url');
        foreach ($detailImages as $url) {
            $usedFiles->push(basename($url));
        }

        // 2. From Inspection (overall_photos)
        $inspections = \App\Models\Inspection::whereNotNull('overall_photos')->get(['overall_photos']);
        foreach ($inspections as $ins) {
            // Get raw attribute to prevent accessor mutating it
            $photos = $ins->getAttributes()['overall_photos'] ?? '[]';
            $photosArr = is_string($photos) ? json_decode($photos, true) : $photos;
            if (is_array($photosArr)) {
                foreach ($photosArr as $photoUrl) {
                    if ($photoUrl) {
                        $usedFiles->push(basename($photoUrl));
                    }
                }
            }
        }

        $usedFiles = $usedFiles->filter()->unique()->values();

        $deleted = 0;
        $freedBytes = 0;

        foreach ($files as $file) {
            $filename = basename($file);
            
            // Skip actively used files immediately
            if ($usedFiles->contains($filename)) {
                continue;
            }

            $shouldDelete = false;

            if ($hours === 0) {
                $shouldDelete = true;
            } else {
                $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));
                $shouldDelete = $lastModified->lt($threshold);
            }

            if ($shouldDelete) {
                $freedBytes += $disk->size($file);
                $disk->delete($file);
                $deleted++;
            }
        }

        $freedMB = round($freedBytes / 1024 / 1024, 2);
        $this->info("Cleaned up {$deleted} orphan files ({$freedMB} MB freed).");

        return self::SUCCESS;
    }
}
