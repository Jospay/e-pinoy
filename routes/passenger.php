<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user_type:passenger'])->name('passenger.')->group(function () {
    Route::get('/download-app', fn() => Inertia::render('passenger/dashboard/Index'))->name('dashboard');
});
