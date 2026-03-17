<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inspection_id',
        'item_id',
        'is_failed',
        'score_awarded',
        'image_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_failed' => 'boolean',
        'score_awarded' => 'integer',
    ];

    /**
     * Get the inspection this detail belongs to.
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Get the checklist item this detail is for.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'item_id');
    }
}
