<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperationalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\RequestHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pengajuan Reimbursement
    Route::prefix('pengajuan/reimbursement')->name('pengajuan.reimbursement.')->group(function () {
        Route::get('/create', [ReimbursementController::class, 'create'])->name('create');
        Route::post('/', [ReimbursementController::class, 'store'])->name('store');
    });

    // Pengajuan Konsumsi / Operasional
    Route::prefix('pengajuan/konsumsi-operasional')->name('pengajuan.operasional.')->group(function () {
        Route::get('/create', [OperationalController::class, 'create'])->name('create');
        Route::post('/', [OperationalController::class, 'store'])->name('store');
    });

    // Pengajuan Cuti
    Route::prefix('pengajuan/cuti')->name('pengajuan.cuti.')->group(function () {
        Route::get('/create', [LeaveController::class, 'create'])->name('create');
        Route::post('/', [LeaveController::class, 'store'])->name('store');
        Route::get('/quota', [LeaveController::class, 'quota'])->name('quota');
    });

    // Pengajuan Perjalanan Dinas & Settlement
    Route::prefix('pengajuan/perjalanan-dinas')->name('pengajuan.perjalanan-dinas.')->group(function () {
        Route::get('/create', [\App\Http\Controllers\BusinessTripController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\BusinessTripController::class, 'store'])->name('store');
        Route::get('/{id}/settlement', [\App\Http\Controllers\BusinessTripController::class, 'settlementCreate'])->name('settlement.create');
        Route::post('/{id}/settlement', [\App\Http\Controllers\BusinessTripController::class, 'settlementStore'])->name('settlement.store');
    });

    // Riwayat Pengajuan & Detail
    Route::get('/riwayat-pengajuan', [RequestHistoryController::class, 'index'])->name('riwayat-pengajuan.index');
    Route::get('/riwayat-pengajuan/{type}/{id}', [RequestHistoryController::class, 'show'])->name('riwayat-pengajuan.show');
    Route::delete('/riwayat-pengajuan/{type}/{id}', [RequestHistoryController::class, 'destroy'])->name('riwayat-pengajuan.destroy');

    // Notifikasi
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifikasi.mark-as-read');
    Route::post('/notifikasi/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifikasi.read-all');

    // Approval (Atasan / Manager & HRD/Finance)
    Route::prefix('approval')->name('approval.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/riwayat', [ApprovalController::class, 'history'])->name('history');
        Route::post('/{type}/{id}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{type}/{id}/reject', [ApprovalController::class, 'reject'])->name('reject');
        Route::post('/{type}/{id}/unapprove', [ApprovalController::class, 'unapprove'])->name('unapprove');
    });

    // Pembayaran (HRD / Finance)
    Route::post('/pembayaran/{type}/{id}', [PaymentController::class, 'process'])->name('payment.process');

    // Profil Saya
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Laporan & Agregasi (HRD / Finance & Admin)
    Route::middleware('role:admin|hrd_finance')->prefix('admin/reports')->name('admin.reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
        Route::get('/export-excel', [\App\Http\Controllers\Admin\ReportController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export-word', [\App\Http\Controllers\Admin\ReportController::class, 'exportWord'])->name('export-word');
    });

    // Admin Panel (Middleware role: admin)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['create', 'show', 'edit']);
        Route::resource('divisions', \App\Http\Controllers\Admin\DivisionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('expense-types', \App\Http\Controllers\Admin\ExpenseTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('activity-types', \App\Http\Controllers\Admin\ActivityTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('leave-types', \App\Http\Controllers\Admin\LeaveTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('leave-balances', \App\Http\Controllers\Admin\LeaveBalanceController::class)->only(['index', 'store', 'update']);
    });
});

require __DIR__.'/auth.php';
