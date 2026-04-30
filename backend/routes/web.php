<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// guest only
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// auth only
Route::middleware('auth')->group(function () {
    Route::get('/dbAdmin', [AuthController::class, 'showAdminDashboard'])->name('dbAdmin');
    Route::get('/dbSantri', [AuthController::class, 'showSantriDashboard'])->name('dbSantri');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
