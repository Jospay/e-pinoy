<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FranchiseController extends Controller
{
    public function myContract()
    {
        $user = auth()->user();

        // Get the first franchise owned by this user
        $franchise = $user->ownerDetails?->franchises?->first();

        if (! $franchise) {
            return Inertia::render('owner/my-contract/Index', [
                'franchise' => null,
            ]);
        }

        // Prepare contract URLs (you can have multiple if needed)
        $contractUrl = $franchise->contract_attachment
            ? Storage::url($franchise->contract_attachment)
            : null;

        return Inertia::render('owner/my-contract/Index', [
            'franchise' => [
                'id' => $franchise->id,
                'name' => $franchise->name,
                'contract_attachment' => $contractUrl,
            ],
        ]);
    }
}
