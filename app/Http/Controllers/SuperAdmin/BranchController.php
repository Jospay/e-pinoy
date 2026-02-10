<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Franchise;
use App\Http\Resources\SuperAdmin\BranchDatatableResource;
use App\Http\Resources\SuperAdmin\BranchResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class BranchController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'franchise' => ['required', 'string'],
        ]);

        $filters = [
            'franchise' => $validated['franchise'],
        ];

        $branches = Branch::query()
            ->where('franchise_id', $filters['franchise'])
            ->get();

        $franchise = Franchise::with(['owner.user:id,name'])
            ->findOrFail($validated['franchise']);

        return Inertia::render('super-admin/dashboard/BranchIndex', [
            'branches' => BranchDatatableResource::collection($branches),
            'franchise' => [
                'name' => $franchise->name,
                'owner_name' => $franchise->owner?->user?->name ?? 'N/A',
            ],
        ]);
    }

    public function show(Branch $branch)
    {
        $branch->loadMissing(['status:id,name']);

        return new BranchResource($branch);
    }
}
