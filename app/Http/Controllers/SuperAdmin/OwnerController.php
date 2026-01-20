<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\OwnerResource;
use App\Models\UserOwner;
use Illuminate\Http\Request;

class OwnerController extends Controller
{

    public function show(UserOwner $owner)
    {
        // Load relationships and return as JSON
        $owner->loadMissing(['user:id,username,name,email,phone,gender,address,region,city,barangay,province,postal_code']);

        return new OwnerResource($owner);
    }
}
