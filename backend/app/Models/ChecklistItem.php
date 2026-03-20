<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'checklist_id',
        'category',
        'category_vn',
        'category_en',
        'category_kh',
        'content_vn',
        'content_en',
        'content_kh',
        'max_score',
        'is_critical',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'max_score' => 'integer',
        'is_critical' => 'boolean',
    ];

    /**
     * Get the checklist that owns this item.
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Get the inspection details for this item.
     */
    public function inspectionDetails()
    {
        return $this->hasMany(InspectionDetail::class, 'item_id');
    }
}
