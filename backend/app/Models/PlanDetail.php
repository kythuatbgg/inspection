<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlanDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'batch_id',
        'cabinet_code',
        'account_id',
        'status',
        'review_status',
        'review_note',
        'reviewed_at',
    ];

    /**
     * Get the batch this plan detail belongs to.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(InspectionBatch::class, 'batch_id');
    }

    /**
     * Get the cabinet for this plan detail.
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_code', 'cabinet_code');
    }

    /**
     * Get the inspections for this plan detail.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'plan_detail_id');
    }

    /**
     * Get the latest inspection for this plan detail.
     */
    public function inspection(): HasOne
    {
        return $this->hasOne(Inspection::class, 'plan_detail_id')->latestOfMany();
    }

    /**
     * Get the account this plan detail belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
