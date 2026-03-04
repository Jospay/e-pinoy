<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationSchedule extends Model
{
    protected $fillable = [
        'station_reservation_id',
        'bus_station_id',
        'route_step',
        'from_time',
        'to_time',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(StationReservation::class, 'station_reservation_id');
    }

    public function stationReservation()
{
    // Adjust 'station_reservation_id' if your foreign key column has a different name
    return $this->belongsTo(StationReservation::class, 'station_reservation_id');
}

    public function busStation(): BelongsTo
    {
        return $this->belongsTo(BusStation::class, 'bus_station_id');
    }
}
