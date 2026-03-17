<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabinet extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'cabinet_code';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cabinet_code',
        'bts_site',
        'name',
        'type',
        'lat',
        'lng',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    /**
     * Get the plan details for this cabinet.
     */
    public function planDetails(): HasMany
    {
        return $this->hasMany(PlanDetail::class, 'cabinet_code', 'cabinet_code');
    }

    /**
     * Get the inspections for this cabinet.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'cabinet_code', 'cabinet_code');
    }
}
