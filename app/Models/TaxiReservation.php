<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'vehicle_id',
        'passenger_id',
        'status_id',
        'passenger_count',
        'amount',
        'pickup_loc_name',
        'destination_loc_name',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'distance_km',
        'average_speed_kmh',
        'max_speed_kmh',
        'route_path',
        'reserve_date',
        'qrcode_name',
        'payment_options',
        'paymongo_checkout_session_id',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

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
        // Based on your migration: constrained('user_passengers')
        return $this->belongsTo(UserPassenger::class, 'passenger_id');
    }
}
