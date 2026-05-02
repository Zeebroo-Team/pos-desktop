<?php

use Illuminate\Support\Facades\Route;
use Modules\HRManagement\Http\Controllers\HrDepartmentController;
use Modules\HRManagement\Http\Controllers\HrEmployeeController;
use Modules\HRManagement\Http\Controllers\HrJobTitleController;
use Modules\HRManagement\Http\Controllers\HRManagementController;
use Modules\HRManagement\Http\Controllers\HrPayrollController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('hr-management', [HRManagementController::class, 'index'])->name('hr.index');
    Route::get('hr-management/setup', [HRManagementController::class, 'onboarding'])->name('hr.onboarding');
    Route::post('hr-management/setup/decline', [HRManagementController::class, 'declineSetup'])->name('hr.setup.decline');
    Route::post('hr-management/setup/complete', [HRManagementController::class, 'completeSetup'])->name('hr.setup.complete');
    Route::get('hr-management/employees', [HrEmployeeController::class, 'index'])->name('hr.employees.index');
    Route::post('hr-management/employees', [HrEmployeeController::class, 'store'])->name('hr.employees.store');
    Route::get('hr-management/payroll', [HrPayrollController::class, 'index'])->name('hr.payroll.index');
    Route::get('hr-management/departments', [HrDepartmentController::class, 'index'])->name('hr.departments.index');
    Route::post('hr-management/departments', [HrDepartmentController::class, 'store'])->name('hr.departments.store');
    Route::delete('hr-management/departments/{department}', [HrDepartmentController::class, 'destroy'])->name('hr.departments.destroy');
    Route::get('hr-management/designations', [HrJobTitleController::class, 'index'])->name('hr.job-titles.index');
    Route::post('hr-management/designations', [HrJobTitleController::class, 'store'])->name('hr.job-titles.store');
    Route::delete('hr-management/designations/{jobTitle}', [HrJobTitleController::class, 'destroy'])->name('hr.job-titles.destroy');
});
