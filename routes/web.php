<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\FranchiseContractController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LandingPage\ContactUsController; 
use Inertia\Inertia;


Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

Route::post('/contact', [ContactUsController::class, 'store'])->name('contact.store');

Route::get('/inactive', function () {
    return Inertia::render('InactiveAccount');
})->name('inactive')->middleware('check.inactive');

Route::get('dashboard', function (Request $request) {
    $user = $request->user();

    $route = match ($user->userType->name) {
        'driver' => route('driver.dashboard'),
        'passenger' => route('passenger.dashboard'),
        'technician' => route('technician.dashboard'),
        'owner' => route('owner.dashboard'),
        'manager' => route('manager.dashboard'),
        'super_admin' => route('super-admin.dashboard'),
        default => route('home'),
    };

    return redirect($route);

})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth', 'verified', 'check.active'])->group(function () {
    Route::get('/franchise/contract', [FranchiseContractController::class, 'index'])->name('contract');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/driver.php';
require __DIR__ . '/passenger.php';
require __DIR__ . '/technician.php';
require __DIR__ . '/owner.php';
require __DIR__ . '/manager.php';
require __DIR__ . '/finance.php';
require __DIR__ .'/auth.php';
require __DIR__ .'/super-admin.php';
