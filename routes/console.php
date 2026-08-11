<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tugas Terjadwal
|--------------------------------------------------------------------------
| Agar ini berjalan, tambahkan satu baris cron di server (cPanel → Cron Jobs),
| dijalankan tiap menit:
|
|   * * * * * cd /home/user/namafolder && php artisan schedule:run >> /dev/null 2>&1
|
| Laravel yang mengatur kapan tiap tugas benar-benar dijalankan, jadi cukup
| satu baris cron untuk semua tugas.
*/

// Pengingat tagihan dikirim tiap pagi jam 08.00 waktu server.
Schedule::command('lumora:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

// Invoice perpanjangan dibuat tiap pagi jam 07.00 — sengaja SEBELUM
// lumora:send-reminders (08.00), supaya invoice yang baru dibuat hari ini
// bisa langsung ikut masuk hitungan pengingat kalau kebetulan jatuh
// temponya sudah dekat.
Schedule::command('lumora:generate-renewal-invoices')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer();

// Auto-suspend dijalankan malam hari (setelah pengingat pagi & jam kerja),
// supaya klien yang bayar siang hari tidak keburu disuspend hanya karena
// urutan waktu proses terjadwal.
Schedule::command('lumora:suspend-overdue')
    ->dailyAt('20:00')
    ->withoutOverlapping()
    ->onOneServer();
