<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
    ];

    public function franchises(): BelongsToMany
    {
        return $this->belongsToMany(Franchise::class)
                    ->withPivot('status_id');
    }

    // relationship to routes, one to many
    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    // relationship to revenues, one to many
    public function revenues(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    // relationship to vehicles, one to many
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    // relationship to user_drivers, many to many (pivot table)
    public function userDrivers(): BelongsToMany
    {
        return $this->belongsToMany(UserDriver::class);
    }

    public function boundaryContracts()
    {
        return $this->belongsToMany(BoundaryContract::class)
                    ->withPivot('amount', 'status_id');
    }
}
