<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationAmount extends Model
{
    protected $table = 'station_amount';

    protected $fillable = [
        'first_bus_station_id',
        'second_bus_station_id',
        'amount',
    ];

    public function fromStation()
    {
        return $this->belongsTo(BusStation::class, 'first_bus_station_id');
    }

    public function toStation()
    {
        return $this->belongsTo(BusStation::class, 'second_bus_station_id');
    }
}
