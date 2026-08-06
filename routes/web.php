<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExecutiveDashboardController;
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

    // Modul Keuangan (Kas Operasional & Tagihan Bulanan)
    Route::middleware('role:admin|hrd_finance')->prefix('keuangan')->name('keuangan.')->group(function () {
        // Kas Operasional
        Route::get('/kas-operasional', [\App\Http\Controllers\CashOperationalController::class, 'dashboard'])->name('kas-operasional.dashboard');
        Route::post('/kas-operasional/accounts', [\App\Http\Controllers\CashOperationalController::class, 'storeAccount'])->name('kas-operasional.accounts.store');
        Route::put('/kas-operasional/accounts/{id}', [\App\Http\Controllers\CashOperationalController::class, 'updateAccount'])->name('kas-operasional.accounts.update');
        Route::post('/kas-operasional/transaksi', [\App\Http\Controllers\CashOperationalController::class, 'storeTransaction'])->name('kas-operasional.transaksi.store');

        // Tagihan Bulanan Rutin
        Route::get('/tagihan-bulanan', [\App\Http\Controllers\MonthlyBillController::class, 'index'])->name('tagihan-bulanan.index');
        Route::post('/tagihan-bulanan/types', [\App\Http\Controllers\MonthlyBillController::class, 'storeBillType'])->name('tagihan-bulanan.types.store');
        Route::put('/tagihan-bulanan/types/{id}', [\App\Http\Controllers\MonthlyBillController::class, 'updateBillType'])->name('tagihan-bulanan.types.update');
        // Pencairan & Pembayaran Keuangan
        Route::get('/pencairan', [\App\Http\Controllers\FinanceDisbursementController::class, 'index'])->name('pencairan.index');
    });

    // Modul Akuntansi
    Route::middleware('role:admin|hrd_finance')->prefix('accounting')->name('accounting.')->group(function () {
        Route::resource('journals', \App\Http\Controllers\Accounting\JournalEntryController::class)->only(['index', 'store', 'show']);
        Route::post('journals/{journal}/void', [\App\Http\Controllers\Accounting\JournalEntryController::class, 'void'])->name('journals.void');
        Route::resource('coas', \App\Http\Controllers\Accounting\CoaController::class)->except(['create', 'show', 'edit']);
        Route::resource('assets', \App\Http\Controllers\Accounting\AssetController::class)->except(['create', 'show', 'edit']);
        Route::resource('periods', \App\Http\Controllers\Accounting\AccountingPeriodController::class)->except(['create', 'show', 'edit']);
        Route::post('periods/{period}/close', [\App\Http\Controllers\Accounting\AccountingPeriodController::class, 'closePeriod'])->name('periods.close');
        Route::get('beginning-balances', [\App\Http\Controllers\Accounting\BeginningBalanceController::class, 'index'])->name('beginning-balances.index');
        Route::post('beginning-balances', [\App\Http\Controllers\Accounting\BeginningBalanceController::class, 'store'])->name('beginning-balances.store');
    });

    // Executive dashboard route protected by role
    Route::middleware('role:admin|manager|hrd_finance')->group(function () {
        Route::get('/executive/dashboard/breakdown', [ExecutiveDashboardController::class, 'breakdown'])->name('executive.dashboard.breakdown');
        Route::get('/executive/dashboard', [ExecutiveDashboardController::class, 'index'])->name('executive.dashboard');
    });

    // Admin Panel (Middleware role: admin)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['create', 'show', 'edit']);
        Route::resource('divisions', \App\Http\Controllers\Admin\DivisionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('expense-types', \App\Http\Controllers\Admin\ExpenseTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('activity-types', \App\Http\Controllers\Admin\ActivityTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('leave-types', \App\Http\Controllers\Admin\LeaveTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('leave-balances', \App\Http\Controllers\Admin\LeaveBalanceController::class)->only(['index', 'store', 'update']);
        Route::resource('services', \App\Http\Controllers\Admin\ServiceController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // Invoicing Module
    Route::prefix('invoicing')->name('invoicing.')->group(function () {
        Route::resource('customers', \App\Http\Controllers\Invoicing\CustomerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('invoices/{invoice}/duplicate', [\App\Http\Controllers\Invoicing\InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::resource('invoices', \App\Http\Controllers\Invoicing\InvoiceController::class)->except(['edit', 'update']);
        Route::get('invoices/{invoice}/pdf', [\App\Http\Controllers\Invoicing\InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::post('invoices/{invoice}/payments', [\App\Http\Controllers\Invoicing\InvoicePaymentController::class, 'store'])->name('invoices.payments.store');
    });

    // Renewal Webpraktis Module
    Route::middleware('role:admin|hrd_finance')->prefix('renewal')->name('renewal.')->group(function () {
        Route::resource('vendors', \App\Http\Controllers\Renewal\VendorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('domains', \App\Http\Controllers\Renewal\DomainController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('renewals', [\App\Http\Controllers\Renewal\RenewalRequestController::class, 'index'])->name('renewals.index');
        Route::post('renewals', [\App\Http\Controllers\Renewal\RenewalRequestController::class, 'store'])->name('renewals.store');
        Route::get('renewals/{renewalRequest}', [\App\Http\Controllers\Renewal\RenewalRequestController::class, 'show'])->name('renewals.show');
        Route::post('renewals/{renewalRequest}/generate-invoice', [\App\Http\Controllers\Renewal\RenewalRequestController::class, 'generateInvoice'])->name('renewals.generate-invoice');
        Route::post('renewals/{renewalRequest}/mark-paid-customer', [\App\Http\Controllers\Renewal\RenewalRequestController::class, 'markPaidCustomer'])->name('renewals.mark-paid-customer');
        Route::post('renewals/{renewalRequest}/vendor-payment', [\App\Http\Controllers\Renewal\VendorPaymentController::class, 'store'])->name('renewals.vendor-payment.store');
        Route::post('renewals/{renewalRequest}/complete', [\App\Http\Controllers\Renewal\RenewalRequestController::class, 'complete'])->name('renewals.complete');
    });
});

require __DIR__.'/auth.php';
