<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StationReservation extends Model
{

    public $timestamps = false;

    protected $fillable = ['vehicle_id'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StationSchedule::class);
    }

    public function dateSchedules(): HasMany
    {
        return $this->hasMany(DateSchedule::class);
    }
}
