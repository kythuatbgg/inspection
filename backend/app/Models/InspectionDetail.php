<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class InspectionDetail extends Model implements HasMedia
{
    use InteractsWithMedia;
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
        'note',
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
     * Ensure the image URL returns the correct host dynamically.
     */
    public function getImageUrlAttribute($value)
    {
        if (!$value) return null;
        
        if (preg_match('/\/storage\/(.+)$/', $value, $matches)) {
            return '/storage/' . $matches[1];
        }
        
        return $value;
    }

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('failure_proof')->singleFile();
    }
}
