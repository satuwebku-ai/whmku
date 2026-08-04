<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\OtpController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\HostingAccountController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RegistrarController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TldController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| Semua route di sini otomatis diberi prefix "admin" dan
| name prefix "admin." lewat bootstrap/app.php.
|
| Order/Invoice/Hosting Account/Domain/Klien memakai pola: daftar per-status
| sebagai halaman terpisah + halaman detail + endpoint aksi terpisah
| (accept/cancel/mark-pending/notes, dst), meniru struktur WHMCS-style yang
| dicontohkan. Server/Registrar/TLD tetap pakai Route::resource karena
| tidak butuh pola status seperti ini.
*/

Route::middleware('guest:admin')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    // Tantangan OTP — pengguna belum login di titik ini, jadi tetap di
    // grup guest. Aksesnya dijaga oleh session "otp.admin_id".
    Route::controller(OtpController::class)->group(function () {
        Route::get('otp/challenge', 'challenge')->name('otp.challenge');
        Route::post('otp/verify', 'verify')->name('otp.verify');
        Route::post('otp/resend', 'resend')->name('otp.resend');
        Route::post('otp/cancel', 'cancel')->name('otp.cancel');
    });
});

Route::middleware('auth:admin')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class . '@index')->name('dashboard');
    Route::get('dashboard', DashboardController::class . '@index')->name('dashboard.alt');

    // ── Order ──
    Route::controller(OrderController::class)->group(function () {
        Route::get('orders', 'orders')->name('orders');
        Route::get('pending/orders', 'pending')->name('orders.pending');
        Route::get('active/orders', 'active')->name('orders.active');
        Route::get('suspended/orders', 'suspended')->name('orders.suspended');
        Route::get('cancelled/orders', 'cancelled')->name('orders.cancelled');
        Route::get('order/details/{order}', 'details')->name('orders.details');

        Route::get('add/order', 'create')->name('order.add.page');
        Route::post('add/order', 'store')->name('order.add');
        Route::get('edit/order/{order}', 'edit')->name('order.edit.page');
        Route::post('update/order/{order}', 'update')->name('order.update');
        Route::delete('delete/order/{order}', 'destroy')->name('order.delete');

        Route::post('accept/order', 'accept')->name('order.accept');
        Route::post('cancel/order', 'cancel')->name('order.cancel');
        Route::post('mark-as-pending/order', 'markPending')->name('order.mark.pending');
        Route::post('order/notes', 'orderNotes')->name('order.notes');
    });

    // ── Invoice ──
    Route::controller(InvoiceController::class)->group(function () {
        Route::get('invoices', 'invoices')->name('invoices');
        Route::get('unpaid/invoices', 'unpaid')->name('invoices.unpaid');
        Route::get('paid/invoices', 'paid')->name('invoices.paid');
        Route::get('overdue/invoices', 'overdue')->name('invoices.overdue');
        Route::get('cancelled/invoices', 'cancelled')->name('invoices.cancelled');
        Route::get('invoice/details/{invoice}', 'details')->name('invoices.details');

        Route::get('add/invoice', 'create')->name('invoice.add.page');
        Route::post('add/invoice', 'store')->name('invoice.add');
        Route::get('edit/invoice/{invoice}', 'edit')->name('invoice.edit.page');
        Route::post('update/invoice/{invoice}', 'update')->name('invoice.update');
        Route::delete('delete/invoice/{invoice}', 'destroy')->name('invoice.delete');

        Route::post('mark-as-paid/invoice', 'markPaid')->name('invoice.mark.paid');
        Route::post('mark-as-unpaid/invoice', 'markUnpaid')->name('invoice.mark.unpaid');
        Route::post('cancel/invoice', 'cancel')->name('invoice.cancel');
        Route::post('invoice/notes', 'invoiceNotes')->name('invoice.notes');
    });

    // ── Hosting Account ──
    Route::controller(HostingAccountController::class)->group(function () {
        Route::get('hosting-accounts', 'hostingAccounts')->name('hosting-accounts');
        Route::get('pending/hosting-accounts', 'pending')->name('hosting-accounts.pending');
        Route::get('active/hosting-accounts', 'active')->name('hosting-accounts.active');
        Route::get('suspended/hosting-accounts', 'suspended')->name('hosting-accounts.suspended');
        Route::get('terminated/hosting-accounts', 'terminated')->name('hosting-accounts.terminated');
        Route::get('hosting-account/details/{hostingAccount}', 'details')->name('hosting-accounts.details');

        Route::get('add/hosting-account', 'create')->name('hosting-account.add.page');
        Route::post('add/hosting-account', 'store')->name('hosting-account.add');
        Route::get('edit/hosting-account/{hostingAccount}', 'edit')->name('hosting-account.edit.page');
        Route::post('update/hosting-account/{hostingAccount}', 'update')->name('hosting-account.update');
        Route::delete('delete/hosting-account/{hostingAccount}', 'destroy')->name('hosting-account.delete');

        Route::post('hosting-account/{hostingAccount}/suspend', 'suspend')->name('hosting-accounts.suspend');
        Route::post('hosting-account/{hostingAccount}/unsuspend', 'unsuspend')->name('hosting-accounts.unsuspend');
        Route::post('hosting-account/{hostingAccount}/terminate', 'terminate')->name('hosting-accounts.terminate');
        Route::post('hosting-account/notes', 'notes')->name('hosting-account.notes');
    });

    // ── Domain ──
    Route::get('domain/search', [DomainController::class, 'search'])->name('domain.search');
    Route::post('domain/search', [DomainController::class, 'search']);

    Route::controller(DomainController::class)->group(function () {
        Route::get('domains', 'domains')->name('domains');
        Route::get('pending/domains', 'pending')->name('domains.pending');
        Route::get('active/domains', 'active')->name('domains.active');
        Route::get('expired/domains', 'expired')->name('domains.expired');
        Route::get('cancelled/domains', 'cancelled')->name('domains.cancelled');
        Route::get('domain/details/{domain}', 'details')->name('domains.details');

        Route::get('add/domain', 'create')->name('domain.add.page');
        Route::post('add/domain', 'store')->name('domain.add');
        Route::get('edit/domain/{domain}', 'edit')->name('domain.edit.page');
        Route::post('update/domain/{domain}', 'update')->name('domain.update');
        Route::delete('delete/domain/{domain}', 'destroy')->name('domain.delete');

        Route::post('domain/{domain}/renew', 'renew')->name('domains.renew');
        Route::post('cancel/domain', 'cancel')->name('domain.cancel');
        Route::post('domain/notes', 'notes')->name('domain.notes');
    });

    // ── Klien ──
    Route::controller(ClientController::class)->group(function () {
        Route::get('clients', 'clients')->name('clients');
        Route::get('active/clients', 'active')->name('clients.active');
        Route::get('inactive/clients', 'inactive')->name('clients.inactive');
        Route::get('client/details/{client}', 'details')->name('clients.details');

        Route::get('add/client', 'create')->name('client.add.page');
        Route::post('add/client', 'store')->name('client.add');
        Route::get('edit/client/{client}', 'edit')->name('client.edit.page');
        Route::post('update/client/{client}', 'update')->name('client.update');
        Route::delete('delete/client/{client}', 'destroy')->name('client.delete');

        Route::post('client/status', 'status')->name('client.status');
        Route::post('client/notes', 'notes')->name('client.notes');
    });

    // ── Server / Panel Hosting (Fase 3) ──
    Route::resource('servers', ServerController::class)->except('show');
    Route::post('servers/{server}/test-connection', [ServerController::class, 'testConnection'])->name('servers.test-connection');

    // ── Registrar & TLD Pricing (Fase 4) ──
    Route::resource('registrars', RegistrarController::class)->except('show');
    Route::post('registrars/{registrar}/test-connection', [RegistrarController::class, 'testConnection'])->name('registrars.test-connection');

    Route::resource('tlds', TldController::class)->except('show');

    // ── Pembayaran (Fase 5) ──
    Route::controller(PaymentController::class)->group(function () {
        Route::get('payments', 'payments')->name('payments');
        Route::get('initiated/payments', 'initiated')->name('payments.initiated');
        Route::get('pending/payments', 'pending')->name('payments.pending');
        Route::get('paid/payments', 'paid')->name('payments.paid');
        Route::get('failed/payments', 'failed')->name('payments.failed');
        Route::get('refunded/payments', 'refunded')->name('payments.refunded');
        Route::get('payment/details/{payment}', 'details')->name('payments.details');

        Route::get('add/payment', 'create')->name('payment.add.page');
        Route::post('add/payment', 'store')->name('payment.add');
        Route::delete('delete/payment/{payment}', 'destroy')->name('payment.delete');

        Route::post('approve/payment', 'approve')->name('payment.approve');
        Route::post('reject/payment', 'reject')->name('payment.reject');
        Route::post('payment/{payment}/check-status', 'checkStatus')->name('payment.check.status');
    });

    // ── Payment Gateway (pengaturan) ──
    Route::controller(PaymentGatewayController::class)->group(function () {
        Route::get('gateways', 'gateways')->name('gateways');
        Route::get('add/gateway', 'create')->name('gateway.add.page');
        Route::post('add/gateway', 'store')->name('gateway.add');
        Route::get('edit/gateway/{gateway}', 'edit')->name('gateway.edit.page');
        Route::post('update/gateway/{gateway}', 'update')->name('gateway.update');
        Route::delete('delete/gateway/{gateway}', 'destroy')->name('gateway.delete');
        Route::post('gateway/status', 'status')->name('gateway.status');
    });

    // ── Support Ticket (Fase 6) ──
    Route::controller(TicketController::class)->group(function () {
        Route::get('tickets', 'tickets')->name('tickets');
        Route::get('open/tickets', 'open')->name('tickets.open');
        Route::get('answered/tickets', 'answered')->name('tickets.answered');
        Route::get('customer-reply/tickets', 'customerReply')->name('tickets.customer-reply');
        Route::get('closed/tickets', 'closed')->name('tickets.closed');
        Route::get('ticket/details/{ticket}', 'details')->name('tickets.details');

        Route::get('add/ticket', 'create')->name('ticket.add.page');
        Route::post('add/ticket', 'store')->name('ticket.add');
        Route::delete('delete/ticket/{ticket}', 'destroy')->name('ticket.delete');

        Route::post('ticket/reply', 'reply')->name('ticket.reply');
        Route::post('ticket/close', 'close')->name('ticket.close');
        Route::post('ticket/reopen', 'reopen')->name('ticket.reopen');
        Route::post('ticket/assign', 'assign')->name('ticket.assign');
        Route::post('ticket/priority', 'priority')->name('ticket.priority');
    });

    // ── CMS: Halaman Statis (Fase 6b) ──
    Route::controller(PageController::class)->group(function () {
        Route::get('pages', 'pages')->name('pages');
        Route::get('add/page', 'create')->name('page.add.page');
        Route::post('add/page', 'store')->name('page.add');
        Route::get('edit/page/{page}', 'edit')->name('page.edit.page');
        Route::post('update/page/{page}', 'update')->name('page.update');
        Route::delete('delete/page/{page}', 'destroy')->name('page.delete');
        Route::post('page/status', 'status')->name('page.status');
        Route::post('check/slug', 'checkSlug')->name('check.slug');
    });

    // ── CMS: Pengumuman ──
    Route::controller(AnnouncementController::class)->group(function () {
        Route::get('announcements', 'announcements')->name('announcements');
        Route::get('add/announcement', 'create')->name('announcement.add.page');
        Route::post('add/announcement', 'store')->name('announcement.add');
        Route::get('edit/announcement/{announcement}', 'edit')->name('announcement.edit.page');
        Route::post('update/announcement/{announcement}', 'update')->name('announcement.update');
        Route::delete('delete/announcement/{announcement}', 'destroy')->name('announcement.delete');
    });

    // ── Pengaturan (umum, SEO, analytics, live chat) ──
    Route::controller(SettingController::class)->prefix('settings')->name('settings.')->group(function () {
        Route::get('general', 'general')->name('general');
        Route::post('general', 'updateGeneral')->name('general.update');
        Route::get('seo', 'seo')->name('seo');
        Route::post('seo', 'updateSeo')->name('seo.update');
        Route::get('analytics', 'analytics')->name('analytics');
        Route::post('analytics', 'updateAnalytics')->name('analytics.update');
        Route::get('livechat', 'livechat')->name('livechat');
        Route::post('livechat', 'updateLivechat')->name('livechat.update');
    });

    // ── Profil & Keamanan Akun ──
    Route::controller(ProfileController::class)->group(function () {
        Route::get('profile', 'edit')->name('profile');
        Route::post('profile', 'update')->name('profile.update');
        Route::post('profile/password', 'updatePassword')->name('profile.password');
        Route::post('profile/two-factor', 'toggleTwoFactor')->name('profile.two-factor');
    });
});
