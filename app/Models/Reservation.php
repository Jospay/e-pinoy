<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_reservation_id',
        'vehicle_id',
        'passenger_id',
        'from_bus_station_id',
        'to_bus_station_id',
        'status_id',
        'passenger_count',
        'amount',
        'reserve_from_time',
        'reserve_to_time',
        'reserve_date',
        'qrcode_name',
        'payment_options',
        'paymongo_checkout_session_id',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function passengerOwner(): BelongsTo
    {
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

    // Change this function name and type
    public function taxiReservations() // Added 's'
    {
        // Changed hasOne to hasMany
        return $this->hasMany(TaxiReservation::class, 'reservation_id');
    }
}
