<?php

use Illuminate\Support\Facades\Route;
use Modules\HRManagement\Http\Controllers\HrAllowanceTypeController;
use Modules\HRManagement\Http\Controllers\HrBusinessHolidayController;
use Modules\HRManagement\Http\Controllers\HrComplaintController;
use Modules\HRManagement\Http\Controllers\HrDepartmentController;
use Modules\HRManagement\Http\Controllers\HrEmployeeController;
use Modules\HRManagement\Http\Controllers\HrEmployeePortalController;
use Modules\HRManagement\Http\Controllers\HrJobTitleController;
use Modules\HRManagement\Http\Controllers\HrLeaveRequestController;
use Modules\HRManagement\Http\Controllers\HRManagementController;
use Modules\HRManagement\Http\Controllers\HrPayrollController;

Route::get('hr-portal/login', [HrEmployeePortalController::class, 'showLogin'])->name('hr.portal.login');
Route::post('hr-portal/login', [HrEmployeePortalController::class, 'login'])->name('hr.portal.login.submit');

Route::middleware('auth')->group(function (): void {
    Route::get('hr-portal', [HrEmployeePortalController::class, 'dashboard'])->name('hr.portal.dashboard');
    Route::get('hr-portal/profile', [HrEmployeePortalController::class, 'profile'])->name('hr.portal.profile');
    Route::get('hr-portal/leaves', [HrEmployeePortalController::class, 'leaves'])->name('hr.portal.leaves');
    Route::get('hr-portal/complaints', [HrEmployeePortalController::class, 'complaints'])->name('hr.portal.complaints');
    Route::post('hr-portal/complaints', [HrEmployeePortalController::class, 'storeComplaint'])->name('hr.portal.complaints.store');
    Route::get('hr-portal/salary', [HrEmployeePortalController::class, 'salary'])->name('hr.portal.salary');
    Route::post('hr-portal/switch-employer', [HrEmployeePortalController::class, 'switchEmployer'])->name('hr.portal.switch-employer');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('hr-management', [HRManagementController::class, 'index'])->name('hr.index');
    Route::get('hr-management/leave-requests', [HrLeaveRequestController::class, 'index'])->name('hr.leave-requests.index');
    Route::post('hr-management/employees/{employee}/leave-requests', [HrLeaveRequestController::class, 'storeForEmployee'])->name('hr.employees.leave-requests.store');
    Route::patch('hr-management/leave-requests/{leaveRequest}', [HrLeaveRequestController::class, 'updateStatus'])->name('hr.leave-requests.update');
    Route::post('hr-management/hr-complaints', [HrComplaintController::class, 'store'])->name('hr.complaints.store');
    Route::patch('hr-management/hr-complaints/{hrComplaint}', [HrComplaintController::class, 'updateStatus'])->name('hr.complaints.update');
    Route::get('hr-management/setup', [HRManagementController::class, 'onboarding'])->name('hr.onboarding');
    Route::post('hr-management/setup/decline', [HRManagementController::class, 'declineSetup'])->name('hr.setup.decline');
    Route::post('hr-management/setup/complete', [HRManagementController::class, 'completeSetup'])->name('hr.setup.complete');
    Route::get('hr-management/employees', [HrEmployeeController::class, 'index'])->name('hr.employees.index');
    Route::post('hr-management/employees', [HrEmployeeController::class, 'store'])->name('hr.employees.store');
    Route::get('hr-management/employees/{employee}', [HrEmployeeController::class, 'show'])->name('hr.employees.show');
    Route::patch('hr-management/employees/{employee}', [HrEmployeeController::class, 'update'])->name('hr.employees.update');
    Route::post('hr-management/employees/{employee}/profile-photo', [HrEmployeeController::class, 'storeProfilePhoto'])->name('hr.employees.profile-photo.store');
    Route::delete('hr-management/employees/{employee}/profile-photo', [HrEmployeeController::class, 'destroyProfilePhoto'])->name('hr.employees.profile-photo.destroy');
    Route::post('hr-management/employees/{employee}/documents', [HrEmployeeController::class, 'storeDocument'])->name('hr.employees.documents.store');
    Route::get('hr-management/employees/{employee}/documents/{document}/download', [HrEmployeeController::class, 'downloadDocument'])->name('hr.employees.documents.download');
    Route::delete('hr-management/employees/{employee}/documents/{document}', [HrEmployeeController::class, 'destroyDocument'])->name('hr.employees.documents.destroy');
    Route::get('hr-management/payroll', [HrPayrollController::class, 'index'])->name('hr.payroll.index');
    Route::get('hr-management/departments', [HrDepartmentController::class, 'index'])->name('hr.departments.index');
    Route::post('hr-management/departments', [HrDepartmentController::class, 'store'])->name('hr.departments.store');
    Route::get('hr-management/departments/growth-overview', [HrDepartmentController::class, 'growthOverview'])->name('hr.departments.growth');
    Route::get('hr-management/departments/{department}', [HrDepartmentController::class, 'show'])->name('hr.departments.show');
    Route::patch('hr-management/departments/{department}', [HrDepartmentController::class, 'update'])->name('hr.departments.update');
    Route::patch('hr-management/departments/{department}/details', [HrDepartmentController::class, 'updateDetails'])->name('hr.departments.details.update');
    Route::patch('hr-management/departments/{department}/leadership', [HrDepartmentController::class, 'updateLeadership'])->name('hr.departments.leadership');
    Route::get('hr-management/departments/{department}/employees/search', [HrDepartmentController::class, 'searchDepartmentEmployees'])->name('hr.departments.employees.search');
    Route::post('hr-management/departments/{department}/members/attach', [HrDepartmentController::class, 'attachMembers'])->name('hr.departments.members.attach');
    Route::delete('hr-management/departments/{department}', [HrDepartmentController::class, 'destroy'])->name('hr.departments.destroy');
    Route::get('hr-management/designations', [HrJobTitleController::class, 'index'])->name('hr.job-titles.index');
    Route::post('hr-management/designations', [HrJobTitleController::class, 'store'])->name('hr.job-titles.store');
    Route::delete('hr-management/designations/{jobTitle}', [HrJobTitleController::class, 'destroy'])->name('hr.job-titles.destroy');
    Route::get('hr-management/allowance-types', [HrAllowanceTypeController::class, 'index'])->name('hr.allowance-types.index');
    Route::post('hr-management/allowance-types', [HrAllowanceTypeController::class, 'store'])->name('hr.allowance-types.store');
    Route::delete('hr-management/allowance-types/{allowanceType}', [HrAllowanceTypeController::class, 'destroy'])->name('hr.allowance-types.destroy');
    Route::post('hr-management/settings/holidays', [HrBusinessHolidayController::class, 'store'])->name('hr.settings.holidays.store');
    Route::delete('hr-management/settings/holidays/{holiday}', [HrBusinessHolidayController::class, 'destroy'])->name('hr.settings.holidays.destroy');
});
