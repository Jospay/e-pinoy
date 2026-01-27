<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleTypeController extends Controller
{
    public function requestUnlock(VehicleType $vehicleType)
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            return back()->with('error', 'No franchise found.');
        }

        // Check if already assigned
        $exists = DB::table('franchise_vehicle_type')
            ->where('franchise_id', $franchise->id)
            ->where('vehicle_type_id', $vehicleType->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Vehicle type already requested or assigned.');
        }

        // Insert with pending status (status_id = 6)
        DB::table('franchise_vehicle_type')->insert([
            'franchise_id' => $franchise->id,
            'vehicle_type_id' => $vehicleType->id,
            'status_id' => 6,
        ]);

        return back()->with('success', 'Unlock request submitted successfully! Waiting for approval.');
    }
}
