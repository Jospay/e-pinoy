<?php

use App\Http\Controllers\Manager\BoundaryContractController;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\DriverApplicationController;
use App\Http\Controllers\Manager\DriverManagementController;
use App\Http\Controllers\Manager\RevenueManagementController;
use App\Http\Controllers\Manager\MaintenanceRequestController;
use App\Http\Controllers\Manager\ExpenseManagementController;
use App\Http\Controllers\Manager\ReportAndAnalyticController;
use App\Http\Controllers\Manager\SupportCenterController;
use App\Http\Controllers\Manager\NotificationController;
use App\Http\Controllers\Manager\PayOutController;
use App\Http\Controllers\Manager\VehicleController;
use App\Http\Controllers\Manager\VehicleDriverController;

use App\Http\Controllers\Manager\DetailsDriverController;
use App\Http\Controllers\Manager\DetailsPayrollController;
use App\Http\Controllers\Manager\PayrollDriverController;
use App\Http\Controllers\Manager\ReportDriverController;

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user_type:manager'])->prefix('manager')->name('manager.')->group(function () {
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
});
