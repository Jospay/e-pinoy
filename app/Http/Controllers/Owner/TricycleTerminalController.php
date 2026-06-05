<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Owner\TricycleTerminalDatatableResource;
use App\Http\Resources\Owner\TricycleTerminalShowResource;
use App\Models\TricycleTerminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TricycleTerminalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        $franchise = $user->ownerDetails->franchises()
            ->whereHas('vehicleTypes', function ($query) {
                $query->where('vehicle_types.name', 'tricycle')
                    ->join(
                        'statuses',
                        'franchise_vehicle_type.status_id',
                        '=',
                        'statuses.id'
                    )
                    ->where('statuses.name', 'active');
            })
            ->first();

        if (! $franchise) {
            abort(403);
        }

        $branchesFilterList = $franchise->branches()
            ->select('id', 'name')
            ->get();

        $filterOptions = collect([
            [
                'id' => 'franchise',
                'name' => $franchise->name,
            ],
        ])->merge(
            $branchesFilterList->map(fn ($branch) => [
                'id' => "branch_{$branch->id}",
                'name' => $branch->name,
            ])
        )->values();

        $selectedScopes = $request->input('scope', ['franchise']);

        $selectedBranchIds = collect($selectedScopes)
            ->filter(fn ($scope) => str_starts_with($scope, 'branch_'))
            ->map(fn ($scope) => (int) str_replace('branch_', '', $scope))
            ->values();

        $allowedBranchIds = $branchesFilterList
            ->pluck('id');

        if (
            $selectedBranchIds
                ->diff($allowedBranchIds)
                ->isNotEmpty()
        ) {
            abort(403);
        }

        $query = TricycleTerminal::query()
            ->where(function ($q) use (
                $selectedScopes,
                $selectedBranchIds,
                $franchise,
            ) {
                if (in_array('franchise', $selectedScopes)) {
                    $q->orWhere(function ($sub) use ($franchise) {
                        $sub->where('franchise_id', $franchise->id)
                            ->whereNull('branch_id');
                    });
                }

                if ($selectedBranchIds->isNotEmpty()) {
                    $q->orWhereIn(
                        'branch_id',
                        $selectedBranchIds
                    );
                }
            });

        $tricycleTerminals = $query
            ->with([
                'status',
                'franchise',
                'branch',
            ])
            ->get();

        return Inertia::render(
            'owner/tricycle-terminal/Index',
            [
                'tricycleTerminals' =>
                    TricycleTerminalDatatableResource::collection(
                        $tricycleTerminals
                    ),

                'filterOptions' => $filterOptions,

                'filters' => [
                    'scope' => $selectedScopes,
                ],
            ]
        );
    }

    public function show(TricycleTerminal $tricycleTerminal)
    {
        $user = Auth::user();
        $franchise = $user->ownerDetails->franchises()
            ->whereHas('vehicleTypes', function ($query) {
                $query->where('vehicle_types.name', 'tricycle')
                    ->join(
                        'statuses',
                        'franchise_vehicle_type.status_id',
                        '=',
                        'statuses.id'
                    )
                    ->where('statuses.name', 'active');
            })
            ->first();

        if (! $franchise) {
            abort(403);
        }

        $ownedBranchIds = $franchise
            ->branches()
            ->pluck('id');

        $authorized =
            (
                $tricycleTerminal->franchise_id === $franchise->id
                && is_null($tricycleTerminal->branch_id)
            )
            ||
            (
                $ownedBranchIds->contains($tricycleTerminal->branch_id)
            );
        abort_unless($authorized, 403);

        $tricycleTerminal->load([
            'status',
            'franchise',
            'branch',
        ]);

        return response()->json(
            new TricycleTerminalShowResource(
                $tricycleTerminal
            )
        );
    }
}