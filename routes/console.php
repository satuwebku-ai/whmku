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
