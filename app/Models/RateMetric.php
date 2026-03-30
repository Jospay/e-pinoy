<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateMetric extends Model
{
    protected $fillable = [
        'vehicle_type_id',
        'flag',
        'per_minute',
        'per_km',
    ];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }
}
