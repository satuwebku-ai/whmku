<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BalanceTopupPaid extends Notification
{
    use Queueable, ResolvesChannels;

    public function __construct(
        public float $amount,
        public float $newBalance,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_name', config('app.name'));
        $amount = 'Rp ' . number_format($this->amount, 0, ',', '.');
        $balance = 'Rp ' . number_format($this->newBalance, 0, ',', '.');

        return (new MailMessage)
            ->subject('Isi Ulang Saldo Berhasil')
            ->greeting("Halo {$notifiable->name},")
            ->line("Isi ulang saldo sebesar **{$amount}** sudah berhasil.")
            ->line("Saldo Anda sekarang: **{$balance}**.")
            ->line('Saldo ini bisa langsung dipakai untuk membayar invoice berikutnya.')
            ->action('Lihat Saldo Saya', route('client.balance'))
            ->salutation("Salam,\n{$site}");
    }

    public function toWhatsApp(object $notifiable): string
    {
        $amount = 'Rp ' . number_format($this->amount, 0, ',', '.');
        $balance = 'Rp ' . number_format($this->newBalance, 0, ',', '.');

        return "Halo {$notifiable->name},\n\n"
            . "Isi ulang saldo sebesar *{$amount}* berhasil.\n"
            . "Saldo Anda sekarang: *{$balance}*.";
    }
}
