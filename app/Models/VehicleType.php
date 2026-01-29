<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
    ];

    // relationship to revenue breakdowns, one to many
    public function revenueBreakdowns(): HasMany
    {
        return $this->hasMany(RevenueBreakdown::class);
    }

    // In VehicleType.php
public function franchises(): BelongsToMany
{
    return $this->belongsToMany(Franchise::class)
                ->withPivot('status_id') // Add this
                ->withTimestamps();
}
}
