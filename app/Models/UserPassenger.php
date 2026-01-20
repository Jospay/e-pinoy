<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPassenger extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'status_id',
        'birth_date',
    ];

    // relationship to user, one to one
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id');
    }

    // relationship to status, one to many
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // relationship to routes, one to many
    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    // relationship to ratings, one to many
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function tipsGiven()
{
    return $this->hasMany(Tip::class, 'passenger_id');
}
}
