<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Status;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketJobController extends Controller
{
    public function index()
    {
        $franchise = $this->getFranchiseOrDefault();
        $franchiseId = $franchise?->id;

        $statuses = Status::whereIn('name', ['active', 'completed'])
            ->get(['id', 'name']);

        return Inertia::render('technician/ticket-job/Index', [
            'franchiseExists' => (bool) $franchise,
            'statuses' => $statuses,
            'maintenance' => $this->getPaginatedMaintenances( $franchiseId),
        ]);
    }

    protected function getFranchiseOrDefault()
    {
        return auth()->user()->technicianDetails?->franchises()->first();
    }

    /**
     * Get paginated tickets/jobs list with relationships.
     */
    protected function getPaginatedMaintenances()
    {
        $perPage = request('per_page', 10);
        $technicianId = auth()->id();

        $query = Maintenance::with(['status', 'technician', 'vehicle.driver', 'inventory'])
            ->when($technicianId, fn($q) => $q->where('technician_id', $technicianId))
            ->orderByDesc('created_at');

        /**
         * Status Filter
         */
        if ($status = request('status')) {
            if ($status !== 'all') {
                $query->whereHas('status', fn($q) => $q->where('name', $status));
            }
        }

        /**
         * Regular paginated detailed jobs
         */
        return $query->when($technicianId, fn($q) => $q->where('technician_id', $technicianId))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn($job) => [
                'id'                   => $job->id,
                'maintenance_type'     => $job->maintenance_type,
                'description'          => $job->description,
                'maintenance_date'     => $job->maintenance_date,
                'next_maintenance_date'=> $job->next_maintenance_date,
                'status'               => $job->status?->name,
                'status_id'            => $job->status?->id,

                'vehicle' => $job->vehicle ? [
                    'id' => $job->vehicle->id,
                    'plate_number' => $job->vehicle->plate_number,
                    'vin' => $job->vehicle->vin,
                    'brand' => $job->vehicle->brand,
                    'model' => $job->vehicle->model,
                    'color' => $job->vehicle->color,
                    'year' => $job->vehicle->year,
                ] : null,

                'technician' => $job->technician ? [
                    'id' => $job->technician->user->id,
                    'name' => $job->technician->user->name,
                    'email' => $job->technician->user->email,
                    'phone' => $job->technician->user->phone,
                ] : null,

                'created_at'           => $job->created_at?->toDateString(),

                'inventory' => $job->inventory ? [
                    'id' => $job->inventory->id,
                    'code_no' => $job->inventory->code_no,
                    'name' => $job->inventory->name,
                    'category' => $job->inventory->category,
                    'specification' => $job->inventory->specification,
                ] : null,
            ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $technicianId = auth()->user()?->technicianDetails?->id;

        $job = Maintenance::with('vehicle')
            ->where('id', $id)
            ->where('technician_id', $technicianId)
            ->first();

        $vehicle = $job->vehicle;

        if (!$job) {
            return response()->json(['message' => 'Maintenance Job not found'], 404);
        }

        if (!$vehicle) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        $request->validate([
            'status_id' => 'required|exists:statuses,id',
        ]);

        $job->update([
            'status_id' => $request->status_id,
        ]);

        $vehicle->update([
            'status_id' => 1,
        ]);

        if ($request->status_id == 16 && $job->inventory) {
            $job->inventory->decrement('quantity', 1);
        }

        return redirect()->back()->with('success', 'Maintenance Job status updated!');
    }
}
