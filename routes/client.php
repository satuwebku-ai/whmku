<?php

use App\Http\Controllers\Client\Auth\ForgotPasswordController;
use App\Http\Controllers\Client\Auth\LoginController;
use App\Http\Controllers\Client\Auth\VerifyEmailController;
use App\Http\Controllers\Client\Auth\RegisterController;
use App\Http\Controllers\Client\CheckoutController;
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
    Route::get('login-bootstrap-preview', [LoginController::class, 'createBootstrap'])->name('login.bootstrap-preview');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::get('register-bootstrap-preview', [RegisterController::class, 'createBootstrap'])->name('register.bootstrap-preview');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');

    // ── Login dengan Google ──
    Route::controller(\App\Http\Controllers\Client\Auth\GoogleAuthController::class)
        ->prefix('auth/google')->name('google.')->group(function () {
            Route::get('redirect', 'redirect')->name('redirect');
            Route::get('callback', 'callback')->name('callback');
        });

    // ── Verifikasi email setelah pendaftaran ──
    Route::controller(VerifyEmailController::class)->prefix('verify')->name('verify.')->group(function () {
        Route::get('/', 'notice')->name('notice');
        Route::post('/', 'verify')->name('submit');
        Route::post('resend', 'resend')->name('resend');
    });

    // ── Lupa password: email → kode → password baru ──
    Route::controller(ForgotPasswordController::class)->prefix('password')->name('password.')->group(function () {
        Route::get('forgot', 'request')->name('request');
        Route::post('email', 'sendCode')->name('email');
        Route::get('verify', 'verifyForm')->name('verify');
        Route::post('verify', 'verifyCode')->name('verify.code');
        Route::get('reset', 'resetForm')->name('reset');
        Route::post('reset', 'reset')->name('update');
    });
});

Route::middleware('auth:client')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Hanya berfungsi kalau sesi ini memang berasal dari admin yang
    // mengimpersonasi (dicek di controller lewat session, bukan di sini),
    // supaya klien biasa yang iseng membuka URL-nya tidak bisa memicu apa pun.
    Route::post('impersonate/stop', [\App\Http\Controllers\Admin\ImpersonateController::class, 'stop'])
        ->name('impersonate.stop');

    Route::get('/', [DashboardController::class, 'indexBootstrap'])->name('dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.alt');
    Route::get('dashboard-bootstrap-preview', [DashboardController::class, 'indexBootstrap'])->name('dashboard.bootstrap-preview');

    // ── Layanan & Domain ──
    Route::controller(ServiceController::class)->group(function () {
        Route::get('services', 'services')->name('services');
        Route::get('services-bootstrap-preview', 'servicesBootstrap')->name('services.bootstrap-preview');
        Route::get('service/{service}', 'service')->name('services.show');
        Route::get('service/{service}/show-bootstrap', 'serviceBootstrap')->name('services.show.bootstrap-preview');
        Route::get('service/{service}/upgrade', 'upgradeForm')->name('services.upgrade');
        Route::get('service/{service}/upgrade-bootstrap', 'upgradeFormBootstrap')->name('services.upgrade.bootstrap-preview');
        Route::post('service/{service}/upgrade', 'requestUpgrade')->name('services.upgrade.request');
        Route::post('service/{service}/upgrade/cancel', 'cancelUpgrade')->name('services.upgrade.cancel');
        Route::get('service/{service}/addons', 'addons')->name('services.addons');
        Route::get('service/{service}/addons-bootstrap', 'addonsBootstrap')->name('services.addons.bootstrap-preview');
        Route::post('service/{service}/addons', 'requestAddon')->name('services.addons.request');
        Route::post('service-addon/{addon}/cancel', 'cancelAddon')->name('services.addons.cancel');
        Route::post('service/{service}/renew-now', 'renewServiceNow')->name('services.renew-now');
        Route::get('domains', 'domains')->name('domains');
        Route::get('domains-bootstrap-preview', 'domainsBootstrap')->name('domains.bootstrap-preview');
        Route::get('domain/{domain}', 'domain')->name('domains.show');
        Route::get('domain/{domain}/show-bootstrap', 'domainBootstrap')->name('domains.show.bootstrap-preview');

        // Login sekali klik ke cPanel & ubah nameserver.
        Route::get('service/{service}/login-panel', 'loginPanel')->name('services.login-panel');
        Route::post('service/{service}/change-password', 'changePanelPassword')->name('services.change-password');
        Route::post('domain/{domain}/nameservers', 'updateNameservers')->name('domains.nameservers');
        Route::post('domain/{domain}/auto-renew', 'toggleDomainAutoRenew')->name('domains.auto-renew');
        Route::post('domain/{domain}/privacy', 'togglePrivacyProtection')->name('domains.privacy');
        Route::post('domain/{domain}/lock', 'toggleDomainLock')->name('domains.lock');
        Route::post('domain/{domain}/renew-now', 'renewDomainNow')->name('domains.renew-now');
        Route::get('domain/{domain}/addons', 'domainAddons')->name('domains.addons');
        Route::get('domain/{domain}/addons-bootstrap', 'domainAddonsBootstrap')->name('domains.addons.bootstrap-preview');
        Route::get('domain/{domain}/documents', 'domainDocuments')->name('domains.documents');
        Route::get('domain/{domain}/documents-bootstrap', 'domainDocumentsBootstrap')->name('domains.documents.bootstrap-preview');
        Route::post('domain/{domain}/documents', 'uploadDomainDocument')->name('domains.documents.upload');
        Route::delete('domain-document/{document}', 'deleteDomainDocument')->name('domains.documents.delete');
        Route::get('domain-document/{document}/file', 'domainDocumentFile')->name('domains.documents.file');
        Route::post('domain/{domain}/forwarding', 'updateDomainForwarding')->name('domains.forwarding');
        Route::post('domain/{domain}/theft-protection', 'toggleTheftProtection')->name('domains.theft-protection');
        Route::get('domain/{domain}/email-forwarding', 'emailForwarding')->name('domains.email-forwarding');
        Route::get('domain/{domain}/email-forwarding-bootstrap', 'emailForwardingBootstrap')->name('domains.email-forwarding.bootstrap-preview');
        Route::post('domain/{domain}/email-forwarding', 'addEmailForwarding')->name('domains.email-forwarding.add');
        Route::delete('domain/{domain}/email-forwarding', 'deleteEmailForwarding')->name('domains.email-forwarding.delete');
        Route::post('domain/{domain}/auth-code', 'requestAuthCode')->name('domains.auth-code');
        Route::get('domain/{domain}/dns', 'dns')->name('domains.dns');
        Route::get('domain/{domain}/dns-bootstrap', 'dnsBootstrap')->name('domains.dns.bootstrap-preview');
        Route::post('domain/{domain}/dns', 'addDnsRecord')->name('domains.dns.add');
        Route::delete('domain/{domain}/dns', 'deleteDnsRecord')->name('domains.dns.delete');
        Route::post('service/{service}/cancel', 'requestCancellation')->name('services.cancel');
        Route::post('service/{service}/cancel/withdraw', 'withdrawCancellation')->name('services.cancel.withdraw');
    });

    // ── Invoice & Pembayaran ──
    Route::controller(InvoiceController::class)->group(function () {
        Route::get('invoices', 'invoices')->name('invoices');
        Route::get('invoices-bootstrap-preview', 'invoicesBootstrap')->name('invoices.bootstrap-preview');
        Route::get('invoice/{invoice}', 'invoice')->name('invoices.show');
        Route::get('invoice/{invoice}/show-bootstrap', 'invoiceBootstrap')->name('invoices.show.bootstrap-preview');
        Route::get('invoice/{invoice}/pdf', 'downloadPdf')->name('invoices.pdf');
        Route::post('invoice/{invoice}/pay', 'pay')->name('invoices.pay');
        Route::get('invoice/{invoice}/pay/duitku-methods', 'duitkuMethods')->name('invoices.duitku-methods');
        Route::get('invoice/{invoice}/pay/duitku-methods-bootstrap', 'duitkuMethodsBootstrap')->name('invoices.duitku-methods.bootstrap-preview');
        Route::post('invoice/{invoice}/pay/duitku', 'payDuitkuMethod')->name('invoices.pay-duitku');
        Route::get('invoice/{invoice}/qris/{gateway}', 'payQris')->name('invoices.qris');
        Route::get('invoice/{invoice}/qris-bootstrap/{gateway}', 'payQrisBootstrap')->name('invoices.qris.bootstrap-preview');
        Route::get('qris-status/{payment}', 'qrisStatus')->name('invoices.qris-status');
        Route::post('payment/{payment}/confirm', 'confirmPayment')->name('payment.confirm');
        Route::get('payment/{payment}/proof', 'proofFile')->name('payment.proof');
    });

    Route::controller(\App\Http\Controllers\Client\BalanceController::class)->prefix('saldo')->group(function () {
        Route::get('/', 'index')->name('balance');
        Route::get('bootstrap-preview', 'indexBootstrap')->name('balance.bootstrap-preview');
        Route::post('isi-ulang', 'topup')->name('balance.topup');
        Route::post('bayar/{invoice}', 'payWithBalance')->name('balance.pay');
    });

    // ── Support Ticket ──
    Route::controller(TicketController::class)->group(function () {
        Route::get('tickets', 'tickets')->name('tickets');
        Route::get('tickets-bootstrap-preview', 'ticketsBootstrap')->name('tickets.bootstrap-preview');
        Route::get('ticket/new', 'create')->name('tickets.create');
        Route::get('ticket/new-bootstrap', 'createBootstrap')->name('tickets.create.bootstrap-preview');
        Route::post('ticket/new', 'store')->name('tickets.store');
        Route::get('ticket/{ticket}', 'ticket')->name('tickets.show');
        Route::get('ticket/{ticket}/show-bootstrap', 'ticketBootstrap')->name('tickets.show.bootstrap-preview');
        Route::post('ticket/{ticket}/reply', 'reply')->name('tickets.reply');
        Route::post('ticket/{ticket}/close', 'close')->name('tickets.close');
    });

    // ── Checkout (Fase 7c) — mengubah keranjang jadi Order + Invoice ──
    Route::controller(CheckoutController::class)->group(function () {
        Route::get('checkout', 'index')->name('checkout');
        Route::get('checkout-bootstrap-preview', 'indexBootstrap')->name('checkout.bootstrap-preview');
        Route::post('checkout', 'store')->name('checkout.store');
        Route::post('checkout/coupon', 'applyCoupon')->name('checkout.coupon');
        Route::delete('checkout/coupon', 'removeCoupon')->name('checkout.coupon.remove');
    });

    // ── Profil ──
    Route::controller(ProfileController::class)->group(function () {
        Route::get('profile', 'edit')->name('profile');
        Route::get('profile-bootstrap-preview', 'editBootstrap')->name('profile.bootstrap-preview');
        Route::post('profile', 'update')->name('profile.update');
        Route::post('profile/password', 'updatePassword')->name('profile.password');
    });
});
