<?php

use App\Enums\AccesibilityOption;
use App\Enums\Expertise;
use App\Enums\Gender;
use App\Enums\IdType;
use App\Enums\Language;
use App\Enums\Shifts;
use App\Models\PaymentOption;
use App\Models\UserType;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/select-user-type', function () {
    // $userTypes = UserType::whereNotIn('name', ['super_admin', 'manager'])
    //     ->get()
    //     ->map(fn($type) => [
    //         'name' => $type->name,
    //         'encrypted_id' => Crypt::encryptString($type->id),
    // ]);
    $userTypes = UserType::whereIn('name', ['driver', 'passenger'])
        ->get()
        ->map(fn($type) => [
            'name' => $type->name,
            'encrypted_id' => Crypt::encryptString($type->id),
        ]);

    return Inertia::render('auth/SelectUserType', [
        'userTypes' => $userTypes,
    ]);
})->name('select-user-type');

Route::get('/register/{user_type}', function ($user_type) {
    try {
        $userTypeId = Crypt::decryptString($user_type);
    } catch (\Exception $e) {
        abort(403, 'Invalid user type.');
    }

    $userType = UserType::where('name', '!=', 'super_admin')->findOrFail($userTypeId);

    $paymentOptions = PaymentOption::all()->map(function ($option) {
        return [
            'id' => (string) $option->id,
            'label' => $option->name,
            'color' => $option->color ?? 'bg-blue-500',
        ];
    });

    return Inertia::render('auth/Register', [
        'userType' => [
            'name' => $userType->name,
            'encrypted_id' => Crypt::encryptString($userType->id),
        ],
        'genderOptions' => Gender::options(),
        'preferredLanguages' => Language::options(),
        'expertise' => Expertise::options(),
        'idTypes' => IdType::options(),
        'accessibilityOptions' => AccesibilityOption::options(),
        'paymentOptions' => $paymentOptions,
        'shifts' => Shifts::options(),
    ]);
})->name('register.dynamic');

Route::get('/register', function () {
    return redirect('select-user-type');
});

