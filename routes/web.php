<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Receipts — export routes must come before the resource to avoid parameter conflicts
    Route::get('/receipts/export/excel', [ReceiptController::class, 'exportExcel'])->name('receipts.export.excel');
    Route::get('/receipts/export/pdf', [ReceiptController::class, 'exportPdf'])->name('receipts.export.pdf');
    Route::post('/receipts/import', [ReceiptController::class, 'importExcel'])->name('receipts.import');
    Route::post('/receipts/import/preview', [ReceiptController::class, 'previewImport'])->name('receipts.import.preview');
    Route::get('/receipts/{receipt}/export/template', [ReceiptController::class, 'exportTemplate'])->name('receipts.export.template');
    Route::post('/receipts/{receipt}/print-alert', [ReceiptController::class, 'printAlert'])->name('receipts.print-alert');
    Route::resource('receipts', ReceiptController::class);

    // Invoices
    Route::get('/invoices/export/excel', [InvoiceController::class, 'exportExcel'])->name('invoices.export.excel');
    Route::get('/invoices/export/pdf', [InvoiceController::class, 'exportPdf'])->name('invoices.export.pdf');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
    Route::post('/invoices/{invoice}/print-alert', [InvoiceController::class, 'printAlert'])->name('invoices.print-alert');
    Route::resource('invoices', InvoiceController::class);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

    // Admin-only routes
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('/telegram', [TelegramController::class, 'index'])->name('telegram.index');
        Route::post('/telegram', [TelegramController::class, 'store'])->name('telegram.store');
        Route::put('/telegram/schedules/{schedule}', [TelegramController::class, 'updateSchedule'])->name('telegram.schedules.update');
        Route::put('/telegram/{telegram}', [TelegramController::class, 'update'])->name('telegram.update');
        Route::delete('/telegram/{telegram}', [TelegramController::class, 'destroy'])->name('telegram.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});

