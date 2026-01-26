<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // relationship to revenue breakdowns, one to many
    public function revenueBreakdowns(): HasMany
    {
        return $this->hasMany(RevenueBreakdown::class);
    }
}
