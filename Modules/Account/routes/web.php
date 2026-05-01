<?php

use Illuminate\Support\Facades\Route;
use Modules\Account\Http\Controllers\AccountController;
use Modules\Account\Http\Controllers\LoanController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('account/onboarding', [AccountController::class, 'onboarding'])->name('account.onboarding');
    Route::get('loans', [LoanController::class, 'index'])->name('account.loans.index');
    Route::post('loans', [LoanController::class, 'store'])->name('account.loans.store');
    Route::delete('loans/{loan}', [LoanController::class, 'destroy'])->name('account.loans.destroy');
    Route::resource('accounts', AccountController::class)->names('account');
});
