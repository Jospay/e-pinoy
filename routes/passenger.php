<?php

use App\Http\Controllers\Passenger\ReservationController;
use App\Http\Controllers\Passenger\TaxiReservationController;
use App\Http\Controllers\Passenger\TransactionHistoryController;
use App\Http\Controllers\Passenger\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user_type:passenger'])->prefix('passenger')->name('passenger.')->group(function () {
    // Selection Page
        Route::get('/dashboard', [ReservationController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/Reserve', [ReservationController::class, 'create'])->name('reservation.create');
        Route::post('/reservation', [ReservationController::class, 'store'])->name('reservation.store');
        Route::get('/reservation/success/{reservation:qrcode_name}', [ReservationController::class, 'success'])->name('reservation.success');

        Route::get('/reservation/taxi/Reserve/{reservation}', [TaxiReservationController::class, 'index'])->name('reservationtaxi');
        Route::post('/reservation/taxi/reservation', [TaxiReservationController::class, 'store'])->name('reservationtaxi.store');
        Route::get('/reservation/taxi/success/{reservation:id}', [TaxiReservationController::class, 'success'])->name('reservationtaxi.success');

        Route::get('/vehicle-availability', [ReservationController::class, 'getAvailability']);

        Route::get('/transaction-history', [TransactionHistoryController::class, 'index'])->name('transactionhisory');
        Route::post('/transaction-history/refund/{reservation}', [TransactionHistoryController::class, 'refund'])->name('reservation.refund');

        Route::get('/my-wallet', [WalletController::class, 'index'])->name('mywallet');
        Route::get('/my-wallet/infinite', [WalletController::class, 'infiniteTransactions']);
});
