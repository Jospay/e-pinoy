<?php

use App\Http\Controllers\SuperAdmin\StationController;
use App\Http\Controllers\SuperAdmin\AccreditationController;
use App\Http\Controllers\SuperAdmin\AllocationController;
use App\Http\Controllers\SuperAdmin\BoundaryContractController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\DriverController;
use App\Http\Controllers\SuperAdmin\EarningController;
use App\Http\Controllers\SuperAdmin\ExpenseController;
use App\Http\Controllers\SuperAdmin\FeedbackManagementController;
use App\Http\Controllers\SuperAdmin\FranchiseController;
use App\Http\Controllers\SuperAdmin\GpsTrackerController;
use App\Http\Controllers\SuperAdmin\InventoryController;
use App\Http\Controllers\SuperAdmin\OwnerController;
use App\Http\Controllers\SuperAdmin\RevenueController;
use App\Http\Controllers\SuperAdmin\TransactionController;
use App\Http\Controllers\SuperAdmin\VehicleController;
use App\Http\Controllers\SuperAdmin\BranchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'user_type:super_admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::patch('/franchise/{franchise}', [FranchiseController::class, 'accept'])->name('franchise.accept');
    Route::get('/franchise/{franchise}', [FranchiseController::class, 'show'])->name('franchise.show');
    Route::get('/owner/{owner}', [OwnerController::class, 'show'])->name('owner.show');

    Route::get('/branch', [BranchController::class, 'index'])->name('branch.index');
    Route::get('/branch/{branch}', [BranchController::class, 'show'])->name('branch.show');

    Route::get('/revenue', [RevenueController::class, 'index'])->name('revenue.index');
    Route::get('/revenue/show', [RevenueController::class, 'show'])->name('revenue.show');
    Route::get('/revenue/export/index', [RevenueController::class, 'exportIndex'])->name('revenue.export.index');
    Route::get('/revenue/export/show', [RevenueController::class, 'exportShow'])->name('revenue.export.show');

    Route::get('/transaction', [TransactionController::class, 'index'])->name('transaction.index');
    Route::get('/transaction/{id}', [TransactionController::class, 'show'])->name('transaction.show');

    Route::get('/allocation', [AllocationController::class, 'index'])->name('allocation.index');
    Route::post('/allocation', [AllocationController::class, 'store'])->name('allocation.store');
    Route::put('/allocation/{allocation}', [AllocationController::class, 'update'])->name('allocation.update');

    Route::get('/driver', [DriverController::class, 'index'])->name('driver.index');
    Route::get('/driver/verification', [DriverController::class, 'verification'])->name('driver.verification');
    Route::get('/driver/{driver}', [DriverController::class, 'show'])->name('driver.show');
    Route::patch('/driver/{driver}/verify', [DriverController::class, 'verify'])->name('driver.verify');

    Route::get('/vehicle', [VehicleController::class, 'index'])->name('vehicle.index');
    Route::post('/vehicle', [VehicleController::class, 'store'])->name('vehicle.store');
    Route::get('/vehicle/create', [VehicleController::class, 'create'])->name('vehicle.create');
    Route::get('/vehicle/{vehicle}', [VehicleController::class, 'show'])->name('vehicle.show');
    Route::patch('/vehicle/{vehicle}/change', [VehicleController::class, 'changeStatus'])->name('vehicle.change');
    Route::get('/vehicle/{vehicle}/maintenances', [VehicleController::class, 'maintenanceHistory'])->name('vehicle.maintenances');

    Route::get('/accreditation', [AccreditationController::class, 'index'])->name('accreditation.index');
    Route::patch('/accreditation/{franchise}/change', [AccreditationController::class, 'changeStatus'])->name('accreditation.change');

    Route::get('/station', [StationController::class, 'index'])->name('station.index');
    Route::get('/station/{id}', [StationController::class, 'show'])->name('station.show');
    Route::patch('/station/{id}/status', [StationController::class, 'updateStatus'])->name('station.updateStatus');

    Route::get('/earning', [EarningController::class, 'index'])->name('earning.index');
    Route::get('/earning/show', [EarningController::class, 'show'])->name('earning.show');
    Route::get('/earning/export/index', [EarningController::class, 'exportIndex'])->name('earning.export.index');
    Route::get('/earning/export/show', [EarningController::class, 'exportShow'])->name('earning.export.show');

    Route::get('/boundary-contract', [BoundaryContractController::class, 'index'])->name('boundaryContract.index');
    Route::post('/boundary-contract', [BoundaryContractController::class, 'store'])->name('boundaryContract.store');
    Route::get('/boundary-contract/create', [BoundaryContractController::class, 'create'])->name('boundaryContract.create');
    Route::get('/boundary-contract/vehicle-types', [BoundaryContractController::class, 'getVehicleTypes'])->name('boundaryContract.vehicleTypes');
    Route::get('/boundary-contract/drivers', [BoundaryContractController::class, 'getDrivers'])->name('boundaryContract.drivers');
    Route::get('/boundary-contract/{contract}', [BoundaryContractController::class, 'show'])->name('boundaryContract.show');

    Route::get('/gps-tracker', [GpsTrackerController::class, 'index'])->name('gpsTracker.index');

    Route::get('/expense', [ExpenseController::class, 'index'])->name('expense.index');
    Route::get('/expense/show', [ExpenseController::class, 'show'])->name('expense.show');
    Route::get('/expense/export/index', [ExpenseController::class, 'exportIndex'])->name('expense.export.index');
    Route::get('/expense/export/show', [ExpenseController::class, 'exportShow'])->name('expense.export.show');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/{inventory}', [InventoryController::class, 'show'])->name('inventory.show');

    Route::post('/franchise/{franchise}/upload-contract', [FranchiseController::class, 'uploadContract'])
        ->name('franchise.upload-contract');

    Route::resource('feedbacks', FeedbackManagementController::class);
    Route::patch('/feedback/{id}/toggle', [FeedbackManagementController::class, 'toggleActive']);
});
