<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user_type:driver', 'check.active'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('driver/dashboard/Index'))->name('dashboard');
});
