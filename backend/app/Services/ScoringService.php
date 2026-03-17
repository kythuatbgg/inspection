<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Inspection;
use App\Models\InspectionDetail;

class ScoringService
{
    /**
     * Calculate inspection score and determine PASS/FAIL
     */
    public function calculateScore(Inspection $inspection): array
    {
        $checklist = $inspection->checklist;
        $details = $inspection->details;

        // Calculate total score
        $totalScore = 0;
        $criticalErrors = 0;

        foreach ($details as $detail) {
            if ($detail->is_failed) {
                // Check if this is a critical item
                $item = ChecklistItem::find($detail->item_id);
                if ($item && $item->is_critical) {
                    $criticalErrors++;
                }
            } else {
                $totalScore += $detail->score_awarded;
            }
        }

        // Determine PASS/FAIL
        $passesScoreThreshold = $totalScore >= $checklist->min_pass_score;
        $passesCriticalThreshold = $criticalErrors < $checklist->max_critical_allowed;

        $finalResult = ($passesScoreThreshold && $passesCriticalThreshold) ? 'PASS' : 'FAIL';

        return [
            'total_score' => $totalScore,
            'critical_errors_count' => $criticalErrors,
            'final_result' => $finalResult,
        ];
    }

    /**
     * Verify and update inspection with calculated scores
     */
    public function verifyAndScore(Inspection $inspection): Inspection
    {
        $scores = $this->calculateScore($inspection);

        $inspection->update([
            'total_score' => $scores['total_score'],
            'critical_errors_count' => $scores['critical_errors_count'],
            'final_result' => $scores['final_result'],
        ]);

        return $inspection->fresh();
    }
}
