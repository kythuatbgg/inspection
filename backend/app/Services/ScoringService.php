<?php

namespace App\Services;

use App\Models\Inspection;

class ScoringService
{
    /**
     * Calculate inspection score and determine PASS/FAIL
     * Logic:
     *   FAIL = (total_score < 80) OR (critical_errors_count > 1)
     *   PASS = (total_score >= 80) AND (critical_errors_count <= 1)
     */
    public function calculateScore(Inspection $inspection): array
    {
        $checklist = $inspection->checklist;
        $details = $inspection->details()->with('item')->get();

        $totalScore = 0;
        $criticalErrors = 0;

        foreach ($details as $detail) {
            if ($detail->is_failed) {
                // FAILED + CRITICAL → đếm lỗi
                if ($detail->item && $detail->item->is_critical) {
                    $criticalErrors++;
                }
            } else {
                // PASS → cộng điểm score_awarded
                $totalScore += $detail->score_awarded;
            }
        }

        // Logic PASS/FAIL:
        // PASS = (total_score >= 80) AND (critical_errors_count <= 1)
        // FAIL = (total_score < 80) OR (critical_errors_count > 1)
        $passesScoreThreshold = $totalScore >= 80;
        $passesCriticalThreshold = $criticalErrors <= 1;

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
