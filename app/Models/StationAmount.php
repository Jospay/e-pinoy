<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationAmount extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_bus_station_id',
        'to_bus_station_id',
        'amount',
    ];

    public function fromStation(): BelongsTo
    {
        return $this->belongsTo(BusStation::class, 'from_bus_station_id');
    }

    public function toStation(): BelongsTo
    {
        return $this->belongsTo(BusStation::class, 'to_bus_station_id');
    }
}
