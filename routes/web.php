<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return view('dashboard');
    }

    return view('auth::auth.login');
})->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/admin', [DashboardController::class, 'adminPanel'])->middleware('role:admin')->name('admin.panel');
});
