<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\InventoryDatatableResource;
use App\Http\Resources\SuperAdmin\InventoryResource;
use App\Models\Franchise;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use Illuminate\Database\Eloquent\Builder;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'franchise' => ['sometimes', 'nullable', 'array'], 
        ]);

        // 2. Set defaults
        $filters = [
            'franchise' => $validated['franchise'] ?? [],
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters);
        $inventories = $query->get();

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/InventoryIndex', [
            'inventories' => InventoryDatatableResource::collection($inventories),
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
            'filters' => [
                'franchise' => $filters['franchise'],
            ],
        ]);
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $query = Inventory::with('franchise:id,name')
        ->whereNotNull('franchise_id')
        ->when(!empty($filters['franchise']), fn ($q) => $q->whereIn('franchise_id', $filters['franchise']));

        return $query;
    }

    public function show(Inventory $inventory)
    {
        // Load relationships and return as JSON
        $inventory->loadMissing(['franchise:id,name']);

        return new InventoryResource($inventory);
    }
}
