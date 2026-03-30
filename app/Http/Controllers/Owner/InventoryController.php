<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        // We use withSum to get the total quantity from the maintenances table
        $inventory = $franchise
            ? Inventory::where('franchise_id', $franchise->id)
                ->withSum('maintenances as total_used', 'quantity')
                ->latest()
                ->paginate(10)
            : [];

        return Inertia::render('owner/inventory/Index', [
            'inventory' => $inventory,
            'franchise_id' => $franchise?->id
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id'  => 'required|exists:franchises,id',
            'code_no'       => 'required|string|max:100|unique:inventories,code_no',
            'name'          => 'required|string|max:255|unique:inventories,name',
            'category'      => 'required|in:Electrical,Mechanical,Safety Equipment,Consumables,Other',
            'specification' => 'required|string',
            'quantity'      => 'required|integer|min:0',
            'unit_price'    => 'required|numeric|min:0',
            'notes'         => 'nullable|string',
        ]);

        Inventory::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'code_no'       => 'required|string|max:100|unique:inventories,code_no,' . $inventory->id,
            'name'          => 'required|string|max:255|unique:inventories,name,' . $inventory->id,
            'category'      => 'required|in:Electrical,Mechanical,Safety Equipment,Consumables,Other',
            'specification' => 'required|string',
            'quantity'      => 'required|integer|min:0',
            'unit_price'    => 'required|numeric|min:0',
            'notes'         => 'nullable|string',
        ]);

        $inventory->update($validated);

        return redirect()->back();
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        return redirect()->back();
    }
}
