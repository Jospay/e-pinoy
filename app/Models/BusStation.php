<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusStation extends Model
{
    protected $fillable = [
        'franchise_id',
        'status_id',
        'name',
        'code_no',
        'latitude',
        'longitude',
    ];

    public function franchise()
    {
        return $this->belongsTo(Franchise::class);
    }

    public function fromAmounts()
    {
        return $this->hasMany(StationAmount::class, 'first_bus_station_id');
    }

    public function toAmounts()
    {
        return $this->hasMany(StationAmount::class, 'second_bus_station_id');
    }
}
