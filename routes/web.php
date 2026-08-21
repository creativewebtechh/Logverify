<?php

use App\Http\Controllers\Admin\AdminExportController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\MonnifyWebhookController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\Wallet\ReceiptController;
use App\Livewire\AccountSecurity;
use App\Livewire\Admin;
use App\Livewire\Auth;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Livewire\Notifications\Index as Notifications;
use App\Livewire\Orders\Index as Orders;
use App\Livewire\Referrals\Index as Referrals;
use App\Livewire\Services;
use App\Livewire\Wallet;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');
Route::view('/about', 'about')->name('about');

// Auth (guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', Auth\Login::class)->name('login');
    Route::get('/register', Auth\Register::class)->name('register');
    Route::get('/forgot-password', Auth\ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', Auth\ResetPassword::class)->name('password.reset');
});

// Email verification (authenticated but not yet verified)
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', Auth\VerifyEmail::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
});

// Authenticated app (email verified)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/security', AccountSecurity::class)->name('security');

    Route::get('/wallet', Wallet\Fund::class)->name('wallet');
    Route::get('/wallet/transactions', Wallet\Transactions::class)->name('wallet.transactions');
    Route::get('/wallet/transactions/{transaction}/receipt', [ReceiptController::class, 'show'])
        ->name('wallet.transactions.receipt');

    Route::get('/referrals', Referrals::class)->name('referrals');

    Route::get('/numbers', Services\Numbers::class)->name('numbers');
    Route::get('/accounts', Services\Accounts::class)->name('accounts');
    Route::get('/tools', Services\Tools::class)->name('tools');
    Route::get('/boost', Services\BoostOrderForm::class)->name('boost');

    Route::get('/orders', Orders::class)->name('orders');
    Route::get('/notifications', Notifications::class)->name('notifications');
});

// Admin
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\Dashboard::class)->name('dashboard');
    Route::get('/users', Admin\ManageUsers::class)->name('users');
    Route::get('/admins', Admin\ManageAdmins::class)->name('admins');
    Route::get('/sms', Admin\SmsDashboard::class)->name('sms');
    Route::get('/numbers', Admin\ManageNumbers::class)->name('numbers');
    Route::get('/number-services', Admin\ManageNumberServices::class)->name('number-services');
    Route::get('/accounts', Admin\ManageAccounts::class)->name('accounts');
    Route::get('/tools', Admin\ManageTools::class)->name('tools');
    Route::get('/services', Admin\ManageServices::class)->name('services');
    Route::get('/orders', Admin\ManageOrders::class)->name('orders');
    Route::get('/transactions', Admin\ManageTransactions::class)->name('transactions');
    Route::get('/transactions/export', [AdminExportController::class, 'transactions'])->name('transactions.export');
    Route::get('/transactions/export/excel', [AdminExportController::class, 'transactionsExcel'])->name('transactions.export.excel');
    Route::get('/webhook-logs', Admin\WebhookLogs::class)->name('webhooks');
    Route::get('/integrations', Admin\ManageIntegrations::class)->name('integrations');
    Route::get('/settings', Admin\Settings::class)->name('settings');
});

// Webhook (Paystack)
Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle'])
    ->name('paystack.webhook')
    ->middleware('throttle:webhooks')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// Webhook (Monnify)
Route::post('/monnify/webhook', [MonnifyWebhookController::class, 'handle'])
    ->name('monnify.webhook')
    ->middleware('throttle:webhooks')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// Legacy webhook paths (kept for backwards compatibility)
Route::post('/webhook/paystack', [PaystackWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/webhook/monnify', [MonnifyWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// Logout
Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout')->middleware('auth');
