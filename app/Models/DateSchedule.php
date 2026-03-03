<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DateSchedule extends Model
{
    protected $fillable = [
        'station_reservation_id',
        'day_schedule_id'
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(StationReservation::class, 'station_reservation_id');
    }

    public function daySchedule(): BelongsTo
    {
        return $this->belongsTo(DaySchedule::class);
    }
}
