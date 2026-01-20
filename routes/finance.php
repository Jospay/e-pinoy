<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'verified', 'user_type:owner,super_admin', 'check.active'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('finance/dashboard/Index'))->name('dashboard');
    Route::get('/boundary-contracts', fn() => Inertia::render('finance/boundary-contracts/Index'))->name('boundaryContracts');
    Route::get('/revenue-management', fn() => Inertia::render('finance/revenue-management/Index'))->name('revenueManagement');
    Route::get('/expense-management', fn() => Inertia::render('finance/expense-management/Index'))->name('expenseManagement');
    Route::get('/reports-and-analytics', fn() => Inertia::render('finance/reports-and-analytics/Index'))->name('reportsAndAnalytics');
    Route::get('/support-center', fn() => Inertia::render('finance/support-center/Index'))->name('supportCenter');
    Route::get('/notifications', fn() => Inertia::render('finance/notifications/Index'))->name('notifications');
});
