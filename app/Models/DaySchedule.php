<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DaySchedule extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public function dateSchedules(): HasMany
    {
        return $this->hasMany(DateSchedule::class);
    }
}
