<?php

use App\Http\Controllers\Payment\WebhookController;
use App\Http\Controllers\Site\CartController;
use App\Http\Controllers\Site\CatalogController;
use App\Http\Controllers\Site\DomainSearchController;
use App\Http\Controllers\Site\PageController as SitePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');

Route::prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));

Route::prefix('client')->name('client.')->group(base_path('routes/client.php'));

/*
|--------------------------------------------------------------------------
| Toko Publik: Katalog, Cek Domain, Keranjang (Fase 7b)
|--------------------------------------------------------------------------
| Semua route ini bisa diakses TANPA login — pengunjung boleh menjelajah
| katalog, cek domain, dan mengisi keranjang sebelum daftar/login.
| Keranjang disimpan di session (lihat App\Services\Cart\CartService),
| checkout sungguhan (jadi Order + Invoice) menyusul di Fase 7c.
*/
Route::controller(CatalogController::class)->group(function () {
    Route::get('hosting', 'index')->name('catalog.index');
    Route::get('hosting/{category}', 'category')->name('catalog.category');
    Route::get('hosting/{category}/{product}', 'product')->name('catalog.product');
});

Route::controller(DomainSearchController::class)->group(function () {
    Route::get('cek-domain', 'search')->name('domain.search');
    Route::post('cek-domain/keranjang', 'addToCart')->name('domain.add-to-cart');
});

Route::controller(CartController::class)->prefix('keranjang')->name('cart.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('produk', 'addProduct')->name('add-product');
    Route::post('update-siklus', 'updateProductCycle')->name('update-cycle');
    Route::post('update-tahun', 'updateDomainYears')->name('update-years');
    Route::post('hapus', 'remove')->name('remove');
    Route::post('kosongkan', 'clear')->name('clear');
});

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
