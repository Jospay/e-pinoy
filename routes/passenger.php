<?php

use App\Http\Controllers\Passenger\OTPController;
use App\Http\Controllers\Passenger\ReservationController;
use App\Http\Controllers\Passenger\TaxiReservationController;
use App\Http\Controllers\Passenger\TransactionHistoryController;
use App\Http\Controllers\Passenger\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user_type:passenger'])->prefix('passenger')->name('passenger.')->group(function () {
    // Selection Page & Bookings
    Route::get('/dashboard', [ReservationController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/Reserve', [ReservationController::class, 'create'])->name('reservation.create');
    Route::post('/reservation', [ReservationController::class, 'store'])->name('reservation.store');
    Route::get('/reservation/success/{reservation:qrcode_name}', [ReservationController::class, 'success'])->name('reservation.success');

    // Taxi Flow
    Route::get('/reservation/taxi/Reserve/{reservation}', [TaxiReservationController::class, 'index'])->name('reservationtaxi');
    Route::post('/reservation/taxi/reservation', [TaxiReservationController::class, 'store'])->name('reservationtaxi.store');
    Route::get('/reservation/taxi/success/{reservation:id}', [TaxiReservationController::class, 'success'])->name('reservationtaxi.success');

    Route::get('/vehicle-availability', [ReservationController::class, 'getAvailability']);

    // History & Refunds
    Route::get('/transaction-history', [TransactionHistoryController::class, 'index'])->name('transactionhisory');
    Route::post('/transaction-history/refund/{reservation}', [TransactionHistoryController::class, 'refund'])->name('reservation.refund');

    // Wallet Management
    Route::get('/my-wallet', [WalletController::class, 'index'])->name('mywallet');
    Route::get('/my-wallet/infinite', [WalletController::class, 'infiniteTransactions']);
    Route::post('/my-wallet/load', [WalletController::class, 'createLoadSession'])->name('wallet.load');
    Route::get('/my-wallet/success/{userId?}', [WalletController::class, 'loadSuccess'])->name('wallet.success');

    // Resume route for wallet (Called by OTPController after verification)
    Route::get('/my-wallet/resume', [WalletController::class, 'resumeAfterOtp'])->name('wallet.resume_after_otp');

    // --- OTP Routes ---
    Route::get('/verify-phone/{purpose?}', [OTPController::class, 'index'])->name('otp.index');
    Route::post('/send-otp', [OTPController::class, 'sendOtp'])->name('otp.send');

    // --- New Bus Payment Routes ---
    Route::get('/wallet/search-bus', [WalletController::class, 'searchBus'])->name('wallet.search_bus');
    Route::post('/wallet/pay-bus', [WalletController::class, 'payBus'])->name('wallet.pay_bus');
    // ------------------------------

    // Main Unified Verification
    Route::post('/verify-otp', [OTPController::class, 'verifyOtp'])->name('otp.verify');

    // Legacy/Helper for Load Ewallet
    Route::post('/verify-load-ewallet', [OTPController::class, 'verifyLoadEwallet'])->name('verify_load_ewallet');
});
