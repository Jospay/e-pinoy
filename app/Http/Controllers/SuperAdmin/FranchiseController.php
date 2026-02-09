<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\IdType;
use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\FranchiseResource;
use App\Models\Franchise;
use App\Models\Status;
use App\Notifications\AcceptFranchiseApplication;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FranchiseController extends Controller
{
    public function show(Franchise $franchise)
    {
        $franchise->loadMissing(['status:id,name']);

        return new FranchiseResource($franchise);
    }

    public function accept(Franchise $franchise)
    {
        $activeStatus = Status::where('name', 'active')->firstOrFail();

        DB::transaction(function () use ($franchise, $activeStatus) {
            // Update franchise status
            $franchise->status_id = $activeStatus->id;
            $franchise->save();

            // Update the owner's status
            $franchise->owner->status_id = $activeStatus->id;
            $franchise->owner->save();

            $franchise->owner->user->notify(new AcceptFranchiseApplication);
        });

        return back();
    }
}
