<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;

    // protected $fillable = [];

    // relationship to drivers, one to many
    public function drivers(): HasMany
    {
        return $this->hasMany(UserDriver::class);
    }

    // relationship to owners, one to many
    public function owners(): HasMany
    {
        return $this->hasMany(UserOwner::class);
    }

    // relationship to passengers, one to many
    public function passengers(): HasMany
    {
        return $this->hasMany(UserPassenger::class);
    }

    // relationship to managers, one to many
    public function managers(): HasMany
    {
        return $this->hasMany(UserManager::class);
    }

    // relationship to technicians, one to many
    public function technicians(): HasMany
    {
        return $this->hasMany(UserTechnician::class);
    }

    // relationship to franchises, one to many
    public function franchises(): HasMany
    {
        return $this->hasMany(Franchise::class);
    }

    // relationship to expenses, one to many
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // relationship to revenues, one to many
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    // relationship to boundary contracts, one to many
    public function boundaryContracts(): HasMany
    {
        return $this->hasMany(BoundaryContract::class);
    }

    // relationship to vehicles, one to many
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    // relationship to routes, one to many
    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    // relationship to violations, one to many
    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    // relationship to maintenances, one to many
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }
}
