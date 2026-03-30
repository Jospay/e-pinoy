<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PercentageType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AllocationController extends Controller
{
    public function index(): Response
    {
        $percentageTypes = PercentageType::query()
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('super-admin/finance/AllocationIndex', [
            'percentageTypes' => $percentageTypes
        ]);
    }

    public function store(Request $request)
    {
        if (PercentageType::count() >= 7) {
            return back()->withErrors([
                'name' => 'Maximum limit of 7 allocations has been reached.'
            ]);
        }
        $maxLimit = $request->type === 'PHP' ? 10 : 5;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:percentage_types,name'],
            'type' => ['required', Rule::in(['Percentage', 'PHP'])],
            'value' => ['required', 'numeric', 'min:0', "max:$maxLimit"],
        ]);

        PercentageType::create($validated);

        return back();
    }

    public function update(Request $request, PercentageType $allocation)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['Percentage', 'PHP'])],
            'value' => ['required', 'numeric', 'min:0'],
        ]);

        $allocation->update($validated);

        return back();
    }
}
