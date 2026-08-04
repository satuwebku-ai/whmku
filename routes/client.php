<?php

use App\Http\Controllers\Client\Auth\LoginController;
use App\Http\Controllers\Client\Auth\RegisterController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\ServiceController;
use App\Http\Controllers\Client\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client Area Routes
|--------------------------------------------------------------------------
| Diberi prefix "client" dan name prefix "client." lewat web.php.
| Memakai guard "client" yang terpisah dari guard admin, sehingga satu
| browser bisa login sebagai admin dan klien sekaligus tanpa bentrok.
*/

Route::middleware('guest:client')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth:client')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.alt');

    // ── Layanan & Domain ──
    Route::controller(ServiceController::class)->group(function () {
        Route::get('services', 'services')->name('services');
        Route::get('service/{service}', 'service')->name('services.show');
        Route::get('domains', 'domains')->name('domains');
        Route::get('domain/{domain}', 'domain')->name('domains.show');
    });

    // ── Invoice & Pembayaran ──
    Route::controller(InvoiceController::class)->group(function () {
        Route::get('invoices', 'invoices')->name('invoices');
        Route::get('invoice/{invoice}', 'invoice')->name('invoices.show');
        Route::post('invoice/{invoice}/pay', 'pay')->name('invoices.pay');
    });

    // ── Support Ticket ──
    Route::controller(TicketController::class)->group(function () {
        Route::get('tickets', 'tickets')->name('tickets');
        Route::get('ticket/new', 'create')->name('tickets.create');
        Route::post('ticket/new', 'store')->name('tickets.store');
        Route::get('ticket/{ticket}', 'ticket')->name('tickets.show');
        Route::post('ticket/{ticket}/reply', 'reply')->name('tickets.reply');
        Route::post('ticket/{ticket}/close', 'close')->name('tickets.close');
    });

    // ── Profil ──
    Route::controller(ProfileController::class)->group(function () {
        Route::get('profile', 'edit')->name('profile');
        Route::post('profile', 'update')->name('profile.update');
        Route::post('profile/password', 'updatePassword')->name('profile.password');
    });
});
