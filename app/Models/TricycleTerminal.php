<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TricycleTerminal extends Model
{
    use HasFactory;

    protected $fillable = [
        'franchise_id',
        'branch_id',
        'status_id',
        'name',
        'region',
        'province',
        'city',
        'barangay',
        'street',
        'postal_code',
        'latitude',
        'longitude',
    ];

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(UserDriver::class);
    }

    public function boundaryContracts(): HasMany
    {
        return $this->hasMany(BoundaryContract::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
