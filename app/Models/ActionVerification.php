<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionVerification extends Model
{
    protected $fillable = ['user_id', 'action', 'code', 'expires_at'];

    protected $dates = ['expires_at'];
}
