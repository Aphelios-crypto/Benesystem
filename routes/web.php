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
    Route::get('/offices', [EmployeeController::class, 'indexOffices'])->name('offices.index');
    Route::resource('users', \App\Http\Controllers\UserManagementController::class)
        ->except(['create', 'edit', 'show'])
        ->middleware(['role:Super Admin|Admin']);

    // API proxy routes
    Route::get('/api-proxy/employees/{officeUuid}', [EmployeeController::class, 'allEmployees'])->name('employees.all');
    Route::get('/api-proxy/permanent-employees', [EmployeeController::class, 'permanentEmployees'])->name('employees.permanent');
    Route::get('/api-proxy/offices', [EmployeeController::class, 'offices'])->name('offices.list');

    // TEST API Proxy
    Route::get('/test-api/{endpoint}', [\App\Http\Controllers\TestProxyController::class, 'handle'])->where('endpoint', '.*');
});
