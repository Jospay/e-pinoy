<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $franchise = $this->getFranchiseOrDefault();
        $technicianId = $this->getTechnicianId();
        $year = now()->year;

        return Inertia::render('technician/dashboard/Index', [
            'franchiseExists' => (bool) $franchise,
            'pendingRequest' => $this->countRequest($technicianId, 6),
            'activeRequest' => $this->countRequest($technicianId, 1),
            'maintenance' => $this->getMaintenanceJobsSummary( $technicianId),
        ]);
    }

    protected function getTechnicianId()
    {
        return auth()->user()->technicianDetails?->user?->id;
    }

    protected function getFranchiseOrDefault()
    {
        return auth()->user()->technicianDetails?->franchises()->first();
    }

    protected function countRequest(?int $technicianId, int $statusId): int
    {
        return $technicianId
            ? Maintenance::where('technician_id', $technicianId)->where('status_id', $statusId)->count()
            : 0;
    }

    protected function getMaintenanceJobsSummary(?int $technicianId)
    {
        $jobsQuery = Maintenance::with(['status', 'technician', 'vehicle.driver', 'inventory'])
            // ->where('franchise_id', $franchiseId)
            ->where('technician_id', $technicianId)
            // ->whereHas('technician', function ($query) use ($franchiseId) {
            //     $query->where('franchise_id', $franchiseId);
            // })
            ->orderBy('maintenance_date', 'desc');

        if ($search = request('search')) {
            $jobsQuery->where(function ($q) use ($search) {
                $q->whereHas('vehicle', fn($v) =>
                    $v->where('plate_number', 'like', "%{$search}%")
                )
                ->orWhereHas('vehicle.driver', fn($d) =>
                    $d->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                );
            });
        }

        return $jobsQuery->paginate(10)
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
}
