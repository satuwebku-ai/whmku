<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientWelcome extends Notification
{
    use Queueable, ResolvesChannels;

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject("Selamat datang di {$site}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Terima kasih sudah mendaftar di {$site}. Akun Anda sudah aktif dan siap digunakan.")
            ->line('Lewat halaman klien, Anda bisa memesan layanan, melihat tagihan, mengelola domain, dan menghubungi tim support kapan saja.')
            ->action('Buka Halaman Klien', route('client.dashboard'))
            ->line('Kalau ada yang perlu dibantu, balas email ini atau buat tiket support.')
            ->salutation("Salam,\n{$site}");
    }

    public function toWhatsApp(object $notifiable): string
    {
        $site = Setting::get('site_name', config('app.name'));

        return "Halo {$notifiable->name}, selamat datang di {$site}!\n\n"
            . "Akun Anda sudah aktif. Buka halaman klien untuk memesan layanan dan melihat tagihan:\n"
            . route('client.dashboard');
    }
}
