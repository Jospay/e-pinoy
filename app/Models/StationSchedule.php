<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_station_id',
        'from_time',
        'to_time',
    ];

    public function station(): BelongsTo
{
    return $this->belongsTo(BusStation::class, 'bus_station_id');
}
}
