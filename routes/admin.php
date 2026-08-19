<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\OtpController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CronController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\HostingAccountController;
use App\Http\Controllers\Admin\ImpersonateController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\NavMenuController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
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
        Route::get('unlinked/hosting-accounts', 'unlinked')->name('hosting-accounts.unlinked');
        Route::get('hosting-account/details/{hostingAccount}', 'details')->name('hosting-accounts.details');
        Route::get('hosting-account/{hostingAccount}/debug-ssl', 'debugSsl')->name('hosting-accounts.debug-ssl');
        Route::post('hosting-account/{hostingAccount}/retry', 'retryProvisioning')->name('hosting-accounts.retry');
        Route::post('hosting-account/{hostingAccount}/sync', 'syncFromServer')->name('hosting-accounts.sync');
        Route::post('hosting-account/{hostingAccount}/change-password', 'changePassword')->name('hosting-accounts.change-password');

        Route::get('add/hosting-account', 'create')->name('hosting-account.add.page');
        Route::post('add/hosting-account', 'store')->name('hosting-account.add');
        Route::get('edit/hosting-account/{hostingAccount}', 'edit')->name('hosting-account.edit.page');
        Route::post('update/hosting-account/{hostingAccount}', 'update')->name('hosting-account.update');
        Route::delete('delete/hosting-account/{hostingAccount}', 'destroy')->name('hosting-account.delete');

        Route::post('hosting-account/{hostingAccount}/suspend', 'suspend')->name('hosting-accounts.suspend');
        Route::post('hosting-account/{hostingAccount}/unsuspend', 'unsuspend')->name('hosting-accounts.unsuspend');
        Route::post('hosting-account/{hostingAccount}/terminate', 'terminate')->name('hosting-accounts.terminate');
        Route::post('hosting-account/{hostingAccount}/cancellation/approve', 'approveCancellation')->name('hosting-accounts.cancellation.approve');
        Route::post('hosting-account/{hostingAccount}/cancellation/decline', 'declineCancellation')->name('hosting-accounts.cancellation.decline');
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
        Route::post('domain/{domain}/transfer-complete', 'markTransferComplete')->name('domains.transfer-complete');
        Route::post('domain/{domain}/restore', 'restore')->name('domains.restore');
        Route::post('domain/{domain}/retry', 'retryProvisioning')->name('domains.retry');
        Route::post('domain/{domain}/apply-default-ns', 'applyDefaultNameservers')->name('domains.apply-default-ns');
        Route::post('domain/{domain}/eligibility', 'submitEligibility')->name('domains.eligibility');
        Route::post('domain/{domain}/verify-documents', 'verifyDomainDocuments')->name('domains.verify-documents');
        Route::post('domain-document/{document}/review', 'reviewDocument')->name('domain-documents.review');
        Route::get('domain-document/{document}/file', 'documentFile')->name('domain-documents.file');
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
        Route::post('client/{client}/balance', 'adjustBalance')->name('client.balance.adjust');
    });

    // ── Login sebagai Klien ──
    // Dibatasi ke superadmin & admin — staff tidak diberi akses ini karena
    // impersonasi bisa mengubah data klien (order, profil, dsb), bukan
    // sekadar melihat, jadi levelnya sama dengan hak kelola penuh.
    Route::middleware('role:superadmin,admin')
        ->post('client/{client}/impersonate', [ImpersonateController::class, 'start'])
        ->name('client.impersonate');

    // ── Server / Registrar / TLD Pricing / Produk — dibatasi ke
    //    Admin & Superadmin. Semua ini menyangkut kredensial infrastruktur
    //    (API token server, kunci API registrar) atau harga jual yang
    //    memengaruhi seluruh klien — di luar kewenangan Staff yang
    //    tugasnya membantu klien lewat tiket, bukan mengubah harga atau
    //    kredensial sistem.
    Route::middleware('role:admin')->group(function () {
        // ── Server / Panel Hosting (Fase 3) ──
        Route::resource('servers', ServerController::class)->except('show');
        Route::post('servers/{server}/test-connection', [ServerController::class, 'testConnection'])->name('servers.test-connection');
        Route::post('servers/{server}/login-whm', [ServerController::class, 'loginWhm'])->name('servers.login-whm');
        Route::get('servers/{server}/diagnostics', [ServerController::class, 'diagnostics'])->name('servers.diagnostics');

        // ── Registrar & TLD Pricing (Fase 4) ──
        Route::resource('registrars', RegistrarController::class)->except('show');
        Route::post('registrars/{registrar}/test-connection', [RegistrarController::class, 'testConnection'])->name('registrars.test-connection');
        Route::post('registrars/{registrar}/sync-tlds', [RegistrarController::class, 'syncTlds'])->name('registrars.sync-tlds');
        Route::get('registrars/{registrar}/transactions', [RegistrarController::class, 'transactions'])->name('registrars.transactions');
        Route::get('registrars/{registrar}/debug-balance', [RegistrarController::class, 'debugBalance'])->name('registrars.debug-balance');
        Route::get('registrars/{registrar}/diagnostics', [RegistrarController::class, 'diagnostics'])->name('registrars.diagnostics');

        Route::resource('tlds', TldController::class)->except('show');
        Route::post('tld/status', [TldController::class, 'status'])->name('tld.status');
        Route::post('tld/bulk-markup', [TldController::class, 'bulkMarkup'])->name('tld.bulk-markup');
        Route::post('tld/sync-preview', [TldController::class, 'syncPreview'])->name('tld.sync-preview');
        Route::post('tld/import-preview', [TldController::class, 'importPreview'])->name('tld.import-preview');
        Route::post('tld/addon-pricing', [TldController::class, 'updateAddonPricing'])->name('tlds.addon-pricing');
        Route::post('tld/import-apply', [TldController::class, 'importApply'])->name('tld.import-apply');
        Route::post('tld/bulk-update', [TldController::class, 'bulkUpdate'])->name('tld.bulk-update');

        // ── Katalog Produk (Fase 7b) ──
        Route::resource('product-categories', ProductCategoryController::class)->except('show');
        Route::resource('addons', \App\Http\Controllers\Admin\AddonController::class)->except('show');
        Route::post('addon/status', [\App\Http\Controllers\Admin\AddonController::class, 'status'])->name('addon.status');
        Route::resource('products', ProductController::class)->except('show');
        Route::post('product/status', [ProductController::class, 'status'])->name('product.status');
    });

    // ── Pembayaran (Fase 5) — menyetujui/menolak pembayaran adalah aksi
    //    finansial, bukan sekadar "melihat", jadi ikut dibatasi. ──
    Route::middleware('role:admin')->group(function () {
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
            Route::get('payment/{payment}/proof', 'proof')->name('payments.proof');
        });
    });

    // ── Payment Gateway (pengaturan) — kredensial API, bukan daftar
    //    transaksinya (yang tetap boleh dilihat Staff lewat PaymentController
    //    di atas, untuk konteks bantu klien) ──
    Route::middleware('role:admin')->group(function () {
        Route::controller(PaymentGatewayController::class)->group(function () {
            Route::get('gateways', 'gateways')->name('gateways');
            Route::get('add/gateway', 'create')->name('gateway.add.page');
            Route::post('add/gateway', 'store')->name('gateway.add');
            Route::get('edit/gateway/{gateway}', 'edit')->name('gateway.edit.page');
            Route::post('update/gateway/{gateway}', 'update')->name('gateway.update');
            Route::delete('delete/gateway/{gateway}', 'destroy')->name('gateway.delete');
            Route::post('gateway/status', 'status')->name('gateway.status');
        });

        // ── Kupon Diskon — memengaruhi harga jual semua klien ──
        Route::controller(CouponController::class)->group(function () {
            Route::get('coupons', 'coupons')->name('coupons');
            Route::get('add/coupon', 'create')->name('coupon.add.page');
            Route::post('add/coupon', 'store')->name('coupon.add');
            Route::get('edit/coupon/{coupon}', 'edit')->name('coupon.edit.page');
            Route::post('update/coupon/{coupon}', 'update')->name('coupon.update');
            Route::delete('delete/coupon/{coupon}', 'destroy')->name('coupon.delete');
            Route::post('coupon/status', 'status')->name('coupon.status');
        });
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

    // ── CMS: Halaman Statis, Pengumuman, Menu Navigasi — konten situs
    //    publik, mengubah nada bicara/isi resmi perusahaan bukan
    //    wewenang Staff. ──
    Route::middleware('role:admin')->group(function () {
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

        // ── CMS: Banner Promo ──
        Route::controller(\App\Http\Controllers\Admin\PromoBannerController::class)->prefix('promo-banners')->name('promo-banners.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('add', 'create')->name('create');
            Route::post('add', 'store')->name('store');
            Route::get('{promoBanner}/edit', 'edit')->name('edit');
            Route::post('{promoBanner}', 'update')->name('update');
            Route::delete('{promoBanner}', 'destroy')->name('destroy');
            Route::post('status', 'status')->name('status');
            Route::post('{promoBanner}/move', 'move')->name('move');
        });

        // ── CMS: Menu Navigasi Publik ──
        Route::controller(NavMenuController::class)->group(function () {
            Route::get('nav-menus', 'index')->name('nav-menus');
            Route::get('add/nav-menu', 'create')->name('nav-menu.add.page');
            Route::post('add/nav-menu', 'store')->name('nav-menu.add');
            Route::get('edit/nav-menu/{navMenu}', 'edit')->name('nav-menu.edit.page');
            Route::post('update/nav-menu/{navMenu}', 'update')->name('nav-menu.update');
            Route::delete('delete/nav-menu/{navMenu}', 'destroy')->name('nav-menu.delete');
            Route::post('nav-menu/status', 'toggleStatus')->name('nav-menu.status');
            Route::post('nav-menu/{navMenu}/move', 'move')->name('nav-menu.move');
        });
    });

    // ── Pengaturan, Template Notifikasi, Cron Jobs — konfigurasi sistem
    //    murni, tidak ada alasan Staff perlu menyentuh ini untuk
    //    membantu klien lewat tiket. ──
    Route::middleware('role:admin')->group(function () {
        // ── Pengaturan (umum, SEO, analytics, live chat) ──
        Route::controller(SettingController::class)->prefix('settings')->name('settings.')->group(function () {
            Route::get('general', 'general')->name('general');
            Route::post('general', 'updateGeneral')->name('general.update');
            Route::get('seo', 'seo')->name('seo');
            Route::post('seo', 'updateSeo')->name('seo.update');
            Route::get('branding-diagnostics', 'brandingDiagnostics')->name('branding-diagnostics');
            Route::get('analytics', 'analytics')->name('analytics');
            Route::post('analytics', 'updateAnalytics')->name('analytics.update');
            Route::get('notifications', 'notifications')->name('notifications');
            Route::post('notifications', 'updateNotifications')->name('notifications.update');
            Route::post('notifications/test-wa', 'testWhatsApp')->name('notifications.test-wa');
            Route::get('security', 'security')->name('security');
            Route::post('security', 'updateSecurity')->name('security.update');
            Route::post('security/test-recaptcha', 'testRecaptcha')->name('security.test-recaptcha');
            Route::get('livechat', 'livechat')->name('livechat');
            Route::post('livechat', 'updateLivechat')->name('livechat.update');
            Route::post('livechat/test', 'testLiveChat')->name('livechat.test');
        });

        // ── Template Notifikasi (isi/kata-kata tiap email & WhatsApp) ──
        Route::controller(\App\Http\Controllers\Admin\NotificationTemplateController::class)
            ->prefix('notification-templates')->name('notification-templates.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('{key}/edit', 'edit')->name('edit');
                Route::get('{key}/preview', 'preview')->name('preview');
                Route::post('{key}/preview', 'previewDraft')->name('preview.draft');
                Route::post('{key}', 'update')->name('update');
                Route::post('{key}/reset', 'reset')->name('reset');
            });

        // ── Cron Jobs ──
        Route::controller(CronController::class)->prefix('cron')->name('cron.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'update')->name('update');
            Route::post('run/{job}', 'runNow')->name('run');
            Route::post('settings', 'saveSettings')->name('settings');
            Route::post('test-cpanel', 'testCpanel')->name('test-cpanel');
            Route::post('install-cpanel', 'installCpanel')->name('install-cpanel');
        });

        // ── Backup — berisi seluruh data klien, jelas bukan wewenang Staff ──
        Route::controller(\App\Http\Controllers\Admin\BackupController::class)->prefix('backups')->name('backups.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('run', 'runNow')->name('run');
            Route::get('{filename}/download', 'download')->name('download');
            Route::delete('{filename}', 'destroy')->name('destroy');
            Route::post('settings', 'updateSettings')->name('settings');
            Route::post('gdrive-settings', 'updateGoogleDrive')->name('gdrive-settings');
            Route::post('gdrive-test', 'testGoogleDrive')->name('gdrive-test');
        });

        // ── Konsol Web — jalankan perintah artisan tanpa Terminal/SSH ──
        Route::controller(\App\Http\Controllers\Admin\ConsoleController::class)->prefix('console')->name('console.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('run', 'run')->name('run');
        });
    });

    // ── Manajemen Admin & Keamanan (khusus superadmin) ──
    Route::middleware('role:superadmin')->controller(AdminUserController::class)->group(function () {
        Route::get('admins', 'admins')->name('admins');
        Route::get('add/admin', 'create')->name('admin.add.page');
        Route::post('add/admin', 'store')->name('admin.add');
        Route::get('edit/admin/{admin}', 'edit')->name('admin.edit.page');
        Route::post('update/admin/{admin}', 'update')->name('admin.update');
        Route::post('admin/status', 'toggleStatus')->name('admin.status');
        Route::delete('delete/admin/{admin}', 'destroy')->name('admin.delete');

        Route::get('login-attempts', 'loginAttempts')->name('login-attempts');
        Route::post('login-attempts/clear', 'clearAttempts')->name('login-attempts.clear');
    });

    // ── Live Chat ──
    Route::controller(ChatController::class)->group(function () {
        Route::get('chats', 'index')->name('chats');
        Route::get('chat/{chat}', 'show')->name('chats.show');
        Route::get('chat/{chat}/poll', 'poll')->name('chats.poll');
        Route::post('chat/{chat}/reply', 'reply')->name('chats.reply');
        Route::post('chat/{chat}/close', 'close')->name('chats.close');
        Route::delete('chat/{chat}', 'destroy')->name('chats.delete');
    });

    // ── Aktivitas & Broadcast ──
    Route::controller(ActivityController::class)->group(function () {
        Route::get('activities', 'activities')->name('activities');
        Route::post('activities/read-all', 'markAllRead')->name('activities.read-all');
        Route::post('activities/clear-old', 'clearOld')->name('activities.clear-old');
        Route::delete('activity/{activity}', 'destroy')->name('activity.delete');

        Route::get('promo', 'promoForm')->name('promo');
        Route::post('promo', 'sendPromo')->name('promo.send');
    });

    // ── Profil & Keamanan Akun ──
    Route::controller(ProfileController::class)->group(function () {
        Route::get('profile', 'edit')->name('profile');
        Route::post('profile', 'update')->name('profile.update');
        Route::post('profile/password', 'updatePassword')->name('profile.password');
        Route::post('profile/two-factor', 'toggleTwoFactor')->name('profile.two-factor');
    });
});
