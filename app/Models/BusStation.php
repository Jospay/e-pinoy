<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'franchise_id',
        'status_id',
        'name',
        'code_no',
        'latitude',
        'longitude',
    ];

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Relationship for schedules specific to this station
     */
    public function schedules(): HasMany
{
    return $this->hasMany(StationSchedule::class, 'bus_station_id');
}

    public function fromAmounts(): HasMany
    {
        return $this->hasMany(StationAmount::class, 'from_bus_station_id');
    }

    public function toAmounts()
{
    return $this->hasMany(StationAmount::class, 'to_bus_station_id');
}
}
