<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_bus_station_id',
        'to_bus_station_id',
        'passenger_id',
        'status_id',
        'amount',
        'qrcode_name',
        'qrcode_img',
        'paymongo_checkout_session_id',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function passenger(): BelongsTo
    {
        // Adjust 'passenger_id' if your actual FK is different
        return $this->belongsTo(UserPassenger::class, 'passenger_id');
    }

    public function fromStation(): BelongsTo
    {
        return $this->belongsTo(BusStation::class, 'from_bus_station_id');
    }

    public function toStation(): BelongsTo
    {
        return $this->belongsTo(BusStation::class, 'to_bus_station_id');
    }
}
