<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionBatch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'user_id',
        'created_by',
        'checklist_id',
        'start_date',
        'end_date',
        'status',
        'approval_status',
        'approval_note',
        'closed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the user (inspector) assigned to this batch.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the checklist used for this batch.
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Get the plan details for this batch.
     */
    public function planDetails(): HasMany
    {
        return $this->hasMany(PlanDetail::class, 'batch_id');
    }

    /**
     * Get the inspections for this batch.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Get the user who created this batch.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all accounts associated with this batch via plan_details.
     */
    public function accounts()
    {
        return $this->hasManyThrough(
            Account::class,
            PlanDetail::class,
            'batch_id',
            'id',
            'id',
            'account_id'
        )->whereNotNull('account_id')->distinct();
    }
}
