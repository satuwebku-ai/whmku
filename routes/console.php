<?php

use App\Models\Setting;
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

// Backup dini hari (03:00) — jam paling sepi trafik, supaya proses yang
// cukup berat (baca seluruh database + file upload) tidak mengganggu
// klien yang sedang aktif memakai situs.
Schedule::command('lumora:backup')
    ->dailyAt('03:00')
    ->when(fn () => Setting::get('backup_enabled', '1') === '1')
    ->withoutOverlapping()
    ->onOneServer();

// ID Protection yang habis masa berlakunya dimatikan di registrar —
// kalau tidak, kita terus ditagih untuk perlindungan yang sudah tidak
// dibayar klien. Dijalankan setelah pengingat pagi, supaya klien yang
// baru bayar hari itu tidak keburu dimatikan.
Schedule::command('lumora:expire-privacy')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->onOneServer();

// Jaring pengaman — invoice lunas yang layanannya (hosting/domain) belum
// aktif diperbaiki otomatis, tanpa perlu admin sadar atau klik manual.
// Lihat komentar lengkap di ReconcileProvisioning.php soal kenapa ini
// dibutuhkan (kadang pemicu otomatis di event Invoice::updated tidak
// terpicu, mis. kalau invoice sempat ditandai lunas dua kali).
Schedule::command('lumora:reconcile-provisioning')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->onOneServer();

// Trial hosting yang habis masa berlakunya tanpa dibayar -- dicek lebih
// sering (tiap jam) daripada tugas lain karena masa trial cuma 1-7 hari,
// jadi keterlambatan beberapa jam terasa jauh lebih signifikan
// dibandingkan untuk penagihan bulanan biasa.
Schedule::command('lumora:expire-trials')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
