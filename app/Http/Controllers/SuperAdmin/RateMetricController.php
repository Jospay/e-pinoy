<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\RateMetric;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RateMetricController extends Controller
{
    public function index(): Response
    {
        // Fetch specific metrics based on the vehicle type name
        $taxiMetric = RateMetric::whereHas('vehicleType', function ($query) {
            $query->where('name', 'Taxi');
        })->first();

        $tricycleMetric = RateMetric::whereHas('vehicleType', function ($query) {
            $query->where('name', 'Tricycle');
        })->first();

        return Inertia::render('super-admin/finance/RateMetricIndex', [
            'taxiMetric' => $taxiMetric,
            'tricycleMetric' => $tricycleMetric
        ]);
    }

    public function update(Request $request, RateMetric $rateMetric)
    {
        $validated = $request->validate([
            'flag'       => ['required', 'numeric', 'min:0'],
            'per_minute' => ['required', 'numeric', 'min:0'],
            'per_km'     => ['required', 'numeric', 'min:0'],
        ]);

        $rateMetric->update($validated);

        return back()->with('success', 'Rates updated successfully.');
    }
}
