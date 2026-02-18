<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;

// Auth routes
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.page');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(\App\Http\Middleware\ApiAuthenticated::class)->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');

    // API proxy routes
    Route::get('/api-proxy/employees/{officeUuid}', [EmployeeController::class, 'allEmployees'])->name('employees.all');
    Route::get('/api-proxy/permanent-employees', [EmployeeController::class, 'permanentEmployees'])->name('employees.permanent');
});



