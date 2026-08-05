<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderProvisioned extends Notification
{
    use Queueable;

    /**
     * @param  array<int, array{domain: string, username: string, password: string}>  $hostingCredentials
     * @param  array<int, array{domain: string, success: bool, message: string}>  $domainResults
     */
    public function __construct(
        public array $hostingCredentials,
        public array $domainResults,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Pesanan Anda Sudah Diproses')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Pembayaran Anda telah kami terima dan pesanan sedang/sudah diproses. Berikut detailnya:');

        foreach ($this->hostingCredentials as $cred) {
            $mail->line("**Hosting — {$cred['domain']}**")
                ->line("Username cPanel: `{$cred['username']}`")
                ->line("Password: `{$cred['password']}`")
                ->line('⚠️ Segera login dan ganti password ini. Kami tidak menyimpan password Anda, jadi simpan email ini sampai Anda menggantinya.');
        }

        foreach ($this->domainResults as $d) {
            $mail->line($d['success']
                ? "**Domain {$d['domain']}** berhasil didaftarkan."
                : "**Domain {$d['domain']}** belum berhasil diproses otomatis: {$d['message']} Tim kami akan menindaklanjuti secara manual.");
        }

        return $mail
            ->action('Lihat Layanan Saya', route('client.services'))
            ->line('Terima kasih telah menggunakan layanan kami.');
    }
}
