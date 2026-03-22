<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Inspection extends Model implements HasMedia
{
    use InteractsWithMedia;
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
        'overall_photos',
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
        'overall_photos' => 'array',
        'total_score' => 'integer',
        'critical_errors_count' => 'integer',
    ];

    /**
     * Ensure overall photos return the correct host dynamically.
     */
    public function getOverallPhotosAttribute($value)
    {
        $photos = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($photos)) return [];
        
        return array_map(function ($photo) {
            if ($photo && preg_match('/\/storage\/(.+)$/', $photo, $matches)) {
                return '/storage/' . $matches[1];
            }
            return $photo;
        }, $photos);
    }

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('overall_photos');
    }
}
