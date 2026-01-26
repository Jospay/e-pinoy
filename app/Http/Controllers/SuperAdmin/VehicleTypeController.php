<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VehicleTypeController extends Controller
{
    public function index(): Response
    {
        $vehicleTypes = VehicleType::query()
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('super-admin/finance/VehicleType', [
            'percentageTypes' => $vehicleTypes, // keep prop name if frontend expects this
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:vehicle_types,name',
            ],
        ]);

        VehicleType::create($validated);

        return back();
    }

    public function update(Request $request, VehicleType $vehicleType)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vehicle_types', 'name')->ignore($vehicleType->id),
            ],
        ]);

        $vehicleType->update($validated);

        return back();
    }
}
