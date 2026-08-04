<?php

use App\Http\Controllers\Payment\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));

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
