<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'account_code',
    ];

    public function planDetails(): HasMany
    {
        return $this->hasMany(PlanDetail::class);
    }
}
