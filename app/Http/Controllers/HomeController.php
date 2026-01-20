<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Franchise;
use App\Models\UserType;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
    public function index()
    {
        // User types excluding super_admin and manager
        $userTypes = UserType::whereNotIn('name', ['super_admin', 'manager'])
            ->get()
            ->map(fn($type) => [
                'name' => $type->name,
                'encrypted_id' => Crypt::encryptString($type->id),
            ]);

        // Feedbacks with user type
        $feedbacks = Feedback::with('userType:id,name')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($fb) => [
                'id' => $fb->id,
                'name' => $fb->name,
                'avatar' => $fb->avatar,
                'rating' => $fb->rating,
                'description' => $fb->description,
                'user_type' => $fb->userType ? [
                    'id' => $fb->userType->id,
                    'name' => $fb->userType->name,
                ] : null,
                'created_at' => $fb->created_at->toDateTimeString(),
            ]);

        // Franchises
        $franchises = Franchise::select(['id', 'name', 'region', 'province', 'city', 'latitude', 'longitude'])
            ->get();

        return Inertia::render('Home', [
            'canRegister' => Features::enabled(Features::registration()),
            'userTypes' => $userTypes,
            'franchises' => $franchises,
            'feedbacks' => $feedbacks,
        ]);
    }
}
