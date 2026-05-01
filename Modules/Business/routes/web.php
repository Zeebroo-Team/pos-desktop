<?php

use Illuminate\Support\Facades\Route;
use Modules\Business\Http\Controllers\BranchController;
use Modules\Business\Http\Controllers\BusinessController;

Route::middleware(['auth'])->group(function (): void {
    Route::post('/business/onboarding', [BusinessController::class, 'storeOnboarding'])->name('business.onboarding.store');
    Route::post('/business/warehouse-intro', [BusinessController::class, 'acknowledgeWarehouseIntro'])
        ->name('business.warehouse-intro.store');

    Route::get('/business/setup-location', [BranchController::class, 'singleLocationSetup'])
        ->name('business.single-branch.setup');

    Route::get('/branches', [BranchController::class, 'index'])->name('business.branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('business.branches.store');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('business.branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('business.branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('business.branches.destroy');
});
