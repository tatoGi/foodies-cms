<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Authentication endpoints are grouped here. Current project auth
| is admin-focused and keeps the same /admin/* URLs and admin.* names.
|
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminLoginController::class, 'show'])->name('login');
    Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');
    Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
});

Route::redirect('login', '/admin/login')->name('login');
