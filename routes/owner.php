<?php

use App\Http\Controllers\Owner\BoundaryContractController;
use App\Http\Controllers\Owner\FranchiseController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\DetailsDriverController;
use App\Http\Controllers\Owner\DetailsPayrollController;
use App\Http\Controllers\Owner\DriverApplicationController;
use App\Http\Controllers\Owner\DriverManagementController;
use App\Http\Controllers\Owner\PayrollDriverController;
use App\Http\Controllers\Owner\ReportDriverController;
use App\Http\Controllers\Owner\ExpenseManagementController;
use App\Http\Controllers\Owner\MaintenanceRequestController;
use App\Http\Controllers\Owner\NotificationController;
use App\Http\Controllers\Owner\PayOutController;
use App\Http\Controllers\Owner\ReportAndAnalyticController;
use App\Http\Controllers\Owner\RevenueManagementController;
use App\Http\Controllers\Owner\SupportCenterController;
use App\Http\Controllers\Owner\SuspendDriverController;
use App\Http\Controllers\Owner\VehicleController;
use App\Http\Controllers\Owner\VehicleDriverController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user_type:owner', 'check.active'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('/boundary-contracts', BoundaryContractController::class);
    Route::get('/revenue-management', [RevenueManagementController::class, 'index'])->name('revenueManagement');
    Route::get('/expense-management', [ExpenseManagementController::class, 'index'])->name('expenseManagement');
    Route::get('/reports-and-analytics', [ReportAndAnalyticController::class, 'index'])->name('reportsAndAnalytics');
    Route::get('/support-center', [SupportCenterController::class, 'index'])->name('supportCenter');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/payout', [PayOutController::class, 'index'])->name('payout');

    Route::resource('drivers', DriverManagementController::class);
    Route::resource('drivers-application', DriverApplicationController::class);
    Route::resource('vehicles', VehicleController::class);
    Route::resource('vehicle-drivers', VehicleDriverController::class);

    Route::put('/drivers/{id}/status', [DriverManagementController::class, 'updateStatus'])
    ->name('drivers.updateStatus');

    // export for driver
    Route::get('/earning', [ReportDriverController::class, 'index'])->name('driverownerreport');
    Route::get('/earning/export', [ReportDriverController::class, 'export'])->name('driverownerreport.export');
    Route::get('/earning/details', [DetailsDriverController::class, 'show'])->name('driverownerreport.details');
    Route::get('/earning/details/export', [DetailsDriverController::class, 'exportDetails'])->name('driverownerreport_details.export');

    Route::get('/payroll', [PayrollDriverController::class, 'index'])->name('driverownerpayroll');
    Route::get('/payroll/export', [PayrollDriverController::class, 'export'])->name('driverownerpayroll.export');
    Route::get('/payroll/details', [DetailsPayrollController::class, 'show'])->name('driverownerpayroll.details');
    Route::get('/payroll/details/export', [DetailsPayrollController::class, 'exportDetails'])->name('driverownerpayroll_details.export');
    Route::get('/payroll/details/fetch-route', [DetailsPayrollController::class, 'fetchRouteDetails'])->name('driverownerpayroll_details.fetchRoute');

    Route::resource('maintenance-requests', MaintenanceRequestController::class);

    Route::put('/support-tickets/{ticket}/complete', [SupportCenterController::class, 'markAsCompleted'])
    ->name('supportTickets.complete');

    Route::get('/franchise/my-contract', [FranchiseController::class, 'myContract'])
        ->name('franchise.my-contract');
});
