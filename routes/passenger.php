<?php

use App\Http\Controllers\Passenger\ReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user_type:passenger'])->prefix('passenger')->name('passenger.')->group(function () {
    // Selection Page
    Route::get('/dashboard', [ReservationController::class, 'index'])->name('dashboard');

    // Reservation Transaction Page
    Route::get('/dashboard/Reserve', [ReservationController::class, 'create'])->name('reservation.create');

    // Store Action
    Route::post('/reservation', [ReservationController::class, 'store'])->name('reservation.store');
});
