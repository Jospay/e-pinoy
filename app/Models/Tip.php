<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'passenger_id',
        'driver_id',
        'amount',
    ];

    /**
     * Passenger who gave the tip
     */
    public function passenger()
    {
        return $this->belongsTo(UserPassenger::class, 'id');
    }

    /**
     * Driver who received the tip
     */
    public function driver()
    {
        return $this->belongsTo(UserDriver::class, 'id');
    }
}
