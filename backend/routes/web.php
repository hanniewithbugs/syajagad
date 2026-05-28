<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiInsightController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocalChatbotController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserNotificationController;
use Illuminate\Support\Facades\Auth;

// Halaman landing / welcome terbuka untuk semua
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(Auth::user()->role === 'admin' ? 'dbAdmin' : 'dbSantri');
    }

    return view('welcome');
});

Route::middleware('auth')->get('/dashboard', [AuthController::class, 'redirectToDashboard'])->name('dashboard');

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
    Route::get('/admin/stats', [AdminController::class, 'stats']);
    Route::get('/admin/santri', [AdminController::class, 'listSantri']);
    Route::post('/admin/santri', [AdminController::class, 'storeSantri']);
    Route::get('/admin/santri/{santri}', [AdminController::class, 'showSantri']);
    Route::put('/admin/santri/{santri}', [AdminController::class, 'updateSantri']);
    Route::delete('/admin/santri/{santri}', [AdminController::class, 'deleteSantri']);
    Route::post('/admin/santri/{santri}/invoices', [AdminController::class, 'storeInvoice']);
    Route::post('/admin/invoices/bulk', [AdminController::class, 'storeBulkInvoice']);
    Route::get('/admin/payments', [AdminController::class, 'listPayments']);
    Route::get('/admin/reports/export/{format}', [AdminController::class, 'exportReport']);
    Route::get('/admin/audit-logs', [AdminController::class, 'listAuditLogs']);
    Route::get('/admin/permissions', [AdminController::class, 'listPermissions']);
    Route::put('/admin/users/{admin}/permissions', [AdminController::class, 'updatePermissions']);
    Route::post('/admin/password', [AdminController::class, 'changePassword'])->name('admin.password');
});

Route::middleware(['auth', 'role:santri'])->group(function () {
    Route::get('/dbSantri', [AuthController::class, 'showSantriDashboard'])->name('dbSantri');
    Route::put('/santri/profile', [AuthController::class, 'updateSantriProfile'])->name('santri.profile.update');
    Route::post('/santri/password', [AuthController::class, 'changeSantriPassword'])->name('santri.password');
    Route::get('/ai/payment-insight', [AiInsightController::class, 'paymentInsight'])->name('ai.paymentInsight');
    Route::post('/chatbot/quick', [LocalChatbotController::class, 'quick'])->name('chatbot.quick');
    Route::get('/notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [UserNotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [UserNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/payment/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::post('/payment/confirm', [PaymentController::class, 'confirm'])->name('payment.confirm');
    Route::get('/payment/status/{invoice}', [PaymentController::class, 'status'])->name('payment.status');
});

Route::post('/payment/notification', [PaymentController::class, 'notification'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
    ->name('payment.notification');

// Logout route untuk semua authenticated user
Route::middleware('auth')->post('/logout', [AuthController::class, 'logout'])->name('logout');
