<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory;

    // protected $fillable = [];

    // relationship to driver, one to many
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriver::class);
    }

    // relationship to passenger, one to many
    public function passenger(): BelongsTo
    {
        return $this->belongsTo(UserPassenger::class);
    }

    // relationship to route, one to many
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
