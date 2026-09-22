<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PpobController;
use App\Http\Controllers\TopUpController;
use App\Http\Controllers\TransactionCheckController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\OrderHistoryController as UserOrderController;
use App\Http\Controllers\User\WalletController as UserWalletController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\Webhook\DigiflazzWebhookController;
use Illuminate\Support\Facades\Route;

// Public Pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/beranda', fn () => redirect()->route('home'));

// Game Top Up
Route::get('/top-up-game', [TopUpController::class, 'index'])->name('topup.index');
Route::get('/topup/{slug}', [TopUpController::class, 'show'])->name('topup.show');
Route::post('/topup/checkout', [TopUpController::class, 'checkout'])->name('topup.checkout');

// PPOB
Route::get('/layanan-ppob', [PpobController::class, 'index'])->name('ppob.index');
Route::get('/ppob/{slug}', [PpobController::class, 'show'])->name('ppob.show');
Route::post('/layanan-ppob/checkout', [PpobController::class, 'checkout'])->name('ppob.checkout');

// Tracking / Cek Transaksi
Route::get('/cek-transaksi', [TransactionCheckController::class, 'index'])->name('tracking');

// Invoice & Payment Simulation
Route::get('/invoice/{invoice_number}', [InvoiceController::class, 'show'])->name('invoice.show');
Route::post('/invoice/{invoice_number}/simulate-pay', [InvoiceController::class, 'simulatePay'])->name('invoice.simulate-pay');

// Public API & Webhook Endpoints
Route::post('/api/check-id', [TopUpController::class, 'checkAccount'])->name('api.check-id');
Route::post('/api/inquiry-ppob', [PpobController::class, 'inquiry'])->name('api.inquiry-ppob');
Route::post('/api/validate-voucher', [VoucherController::class, 'validateCode'])->name('api.validate-voucher');
Route::post('/api/webhook/digiflazz', [DigiflazzWebhookController::class, 'handle'])->name('webhook.digiflazz');
Route::post('/webhook/digiflazz', [DigiflazzWebhookController::class, 'handle']);

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/demo-login/{type}', [AuthController::class, 'demoLogin'])->name('demo-login');

// User Protected Area
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
    Route::get('/wallet', [UserWalletController::class, 'index'])->name('user.wallet');
    Route::post('/wallet/deposit', [UserWalletController::class, 'deposit'])->name('user.wallet.deposit');
    Route::get('/orders', [UserOrderController::class, 'index'])->name('user.orders');
});

// Admin Authentication & Entry
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
Route::get('/admin', function () {
    if (auth()->check() && auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('admin.login');
})->name('admin.index');
Route::get('/vak-admin', fn () => redirect()->route('admin.index'));

// Admin Protected Area
Route::prefix('admin')->middleware(['admin'])->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Transactions
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions');
    Route::get('/transactions/{id}', [AdminTransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/{id}/refund', [AdminTransactionController::class, 'refund'])->name('transactions.refund');

    // Products & Pricing
    Route::get('/products', [AdminProductController::class, 'index'])->name('products');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::post('/products/sync-digiflazz', [AdminProductController::class, 'syncDigiflazz'])->name('products.sync-digiflazz');
    Route::post('/products/bulk-margin', [AdminProductController::class, 'bulkMargin'])->name('products.bulk-margin');
    Route::post('/products/batch-update', [AdminProductController::class, 'batchUpdate'])->name('products.batch-update');
    Route::post('/products/changes/{id}/review', [AdminProductController::class, 'reviewChange'])->name('products.changes.review');
    Route::post('/products/changes/mark-all-reviewed', [AdminProductController::class, 'markAllReviewed'])->name('products.changes.mark-all-reviewed');
    Route::post('/products/{id}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy'])->name('products.destroy');

    // Users & Balance Adjustment
    Route::get('/users', [AdminUserController::class, 'index'])->name('users');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}/password', [AdminUserController::class, 'updatePassword'])->name('users.update-password');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/adjust-balance', [AdminUserController::class, 'adjustBalance'])->name('users.adjust-balance');

    // Vouchers
    Route::get('/vouchers', [AdminVoucherController::class, 'index'])->name('vouchers');
    Route::post('/vouchers', [AdminVoucherController::class, 'store'])->name('vouchers.store');
    Route::delete('/vouchers/{id}', [AdminVoucherController::class, 'destroy'])->name('vouchers.destroy');

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');
});
