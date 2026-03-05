<?php

use App\Http\Controllers\Passenger\ReservationController;
use App\Http\Controllers\Passenger\TransactionHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user_type:passenger'])->prefix('passenger')->name('passenger.')->group(function () {
    // Selection Page
        Route::get('/dashboard', [ReservationController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/Reserve', [ReservationController::class, 'create'])->name('reservation.create');
        Route::post('/reservation', [ReservationController::class, 'store'])->name('reservation.store');
        Route::get('/reservation/success/{reservation:qrcode_name}', [ReservationController::class, 'success'])->name('reservation.success');
        Route::get('/transaction-history', [TransactionHistoryController::class, 'index'])->name('transactionhisory');
        Route::get('/vehicle-availability', [ReservationController::class, 'getAvailability']);
});
