<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'checklist_id',
        'plan_detail_id',
        'cabinet_code',
        'lat',
        'lng',
        'total_score',
        'critical_errors_count',
        'final_result',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'total_score' => 'integer',
        'critical_errors_count' => 'integer',
    ];

    /**
     * Get the user who performed this inspection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the checklist used for this inspection.
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Get the plan detail associated with this inspection.
     */
    public function planDetail(): BelongsTo
    {
        return $this->belongsTo(PlanDetail::class, 'plan_detail_id');
    }

    /**
     * Get the cabinet inspected.
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_code', 'cabinet_code');
    }

    /**
     * Get the inspection details.
     */
    public function details(): HasMany
    {
        return $this->hasMany(InspectionDetail::class);
    }
}
