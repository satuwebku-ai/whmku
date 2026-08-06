<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailCode extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        // Sengaja HANYA email: tujuannya membuktikan alamat email itu
        // benar-benar milik pendaftar, jadi mengirim lewat WhatsApp
        // justru menggagalkan maksudnya.
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject("Kode Verifikasi Email — {$site}")
            ->greeting("Halo {$notifiable->name},")
            ->line('Masukkan kode berikut untuk mengaktifkan akun Anda:')
            ->line('**' . $this->code . '**')
            ->line('Kode berlaku 30 menit.')
            ->line('Kalau Anda tidak mendaftar di ' . $site . ', abaikan saja email ini.')
            ->salutation("Salam,\n{$site}");
    }
}
