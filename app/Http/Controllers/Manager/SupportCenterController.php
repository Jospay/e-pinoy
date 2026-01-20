<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use Inertia\Inertia;

class SupportCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branch = $this->getBranchOrDefault();
        $branchId = $branch?->id;

        $query = SupportTicket::with(['status', 'franchise', 'branch', 'user'])
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('created_at', 'desc');

        if ($status = request('status')) {
            if ($status !== 'all') {
                $query->whereHas('status', fn($q) => $q->where('name', $status));
            }
        }

        // Global search
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {

                // Search fields
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $tickets = $query
            ->paginate(10)
            ->through(function ($tickets) {
                return [
                    'id' => $tickets->id,
                    'ticket_code' => $tickets->ticket_code,
                    'type' => $tickets->type,
                    'description' => $tickets->description,

                    'status' => $tickets->status?->name,
                    'status_id' => $tickets->status_id,

                    'date' => $tickets->date,

                    'branch' => $tickets->branch ? [
                        'id' => $tickets->branch->id,
                        'name' => $tickets->branch->name,
                    ] : null,

                    'branch' => $tickets->branch ? [
                        'id' => $tickets->branch->id,
                        'name' => $tickets->branch->name,
                    ] : null,

                    'user' => $tickets->user ? [
                        'id' => $tickets->user->id,
                        'name' => $tickets->user->name,
                        'email' => $tickets->user->email,
                    ] : null,
                ];
            });

        return Inertia::render('manager/support-center/Index', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * Find current franchise
     */
    protected function getBranchOrDefault()
    {
        return auth()->user()->managerDetails?->branches()->first();
    }

    public function markAsCompleted(SupportTicket $ticket)
    {
        if ($ticket->status_id !== 16) {
            $ticket->update([
                'status_id' => 16,
            ]);
        }

        return redirect()->back()->with('success', 'Ticket marked as completed!');
    }
}
