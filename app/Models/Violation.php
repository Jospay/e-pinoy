<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Violation extends Model
{
    use HasFactory;

    // protected $fillable = [];

    // relationship to status, one to many
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // relationship to driver, one to many
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriver::class);
    }

    // relationship to franchise, one to many
    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    // relationship to branch, one to many
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
