<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Halaman landing / welcome terbuka untuk semua
Route::get('/', function () {
    return view('welcome');
});

// Routing untuk guest (belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Routing untuk authenticated user dengan role admin dan santri, dipisah middleware role
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dbAdmin', [AuthController::class, 'showAdminDashboard'])->name('dbAdmin');
});

Route::middleware(['auth', 'role:santri'])->group(function () {
    Route::get('/dbSantri', [AuthController::class, 'showSantriDashboard'])->name('dbSantri');
});

// Logout route untuk semua authenticated user
Route::middleware('auth')->post('/logout', [AuthController::class, 'logout'])->name('logout');