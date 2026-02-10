<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Http\Requests\Owner\StoreBranchRequest;
use App\Http\Resources\SuperAdmin\BranchDatatableResource;
use App\Http\Resources\SuperAdmin\BranchResource;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Enums\Gender;
use App\Enums\IdType;
use App\Models\PaymentOption;
use App\Models\Status;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index(): Response
    {   
        $userId = Auth::user()->id;

        $branches = Branch::whereHas('franchise.owner', function ($query) use ($userId) {
            $query->where('id', $userId);
        })->get();

        return Inertia::render('owner/branch/BranchIndex', [
            'branches' => BranchDatatableResource::collection($branches),
        ]);
    }

    public function show(Branch $branch)
    {
        $branch->loadMissing(['status:id,name']);

        return new BranchResource($branch);
    }
}
