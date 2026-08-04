<?php

use App\Http\Controllers\Payment\WebhookController;
use App\Http\Controllers\Site\PageController as SitePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));

Route::prefix('client')->name('client.')->group(base_path('routes/client.php'));

/*
|--------------------------------------------------------------------------
| Halaman Publik (CMS)
|--------------------------------------------------------------------------
| Halaman statis dan pengumuman yang dikelola lewat menu Konten & Halaman.
| Prefix "/p/" dipakai supaya slug halaman tidak bentrok dengan route
| aplikasi lain (mis. /admin, /payment) sekarang maupun nanti.
*/
Route::get('p/{slug}', [SitePageController::class, 'show'])->name('page.show');
Route::get('announcements', [SitePageController::class, 'announcements'])->name('announcements.index');
Route::get('announcements/{slug}', [SitePageController::class, 'announcement'])->name('announcements.show');

/*
|--------------------------------------------------------------------------
| Webhook Pembayaran (publik)
|--------------------------------------------------------------------------
| Dipanggil oleh server gateway, bukan browser klien. Karena itu route ini
| dikecualikan dari CSRF di bootstrap/app.php. Keamanannya dijamin oleh
| verifikasi signature (Midtrans) / callback token (Xendit) di service
| masing-masing, bukan oleh session.
|
| URL yang didaftarkan di dashboard gateway:
|   Midtrans -> https://domainmu.com/payment/webhook/midtrans
|   Xendit   -> https://domainmu.com/payment/webhook/xendit
*/
Route::post('payment/webhook/{driver}', [WebhookController::class, 'handle'])
    ->name('payment.webhook');

Route::get('payment/finish', [WebhookController::class, 'finish'])
    ->name('payment.finish');
