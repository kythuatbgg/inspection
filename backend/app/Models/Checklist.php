<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checklist extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'min_pass_score',
        'max_critical_allowed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_pass_score' => 'integer',
        'max_critical_allowed' => 'integer',
    ];

    /**
     * Get the checklist items for this checklist.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    /**
     * Get the inspection batches using this checklist.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(InspectionBatch::class);
    }

    /**
     * Get the inspections using this checklist.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }
}
