<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaid extends Notification
{
    use Queueable, ResolvesChannels;

    public function __construct(public Invoice $invoice) {}

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_name', config('app.name'));
        $total = 'Rp ' . number_format((float) $this->invoice->total, 0, ',', '.');

        return (new MailMessage)
            ->subject("Pembayaran Diterima — {$this->invoice->invoice_number}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Terima kasih, pembayaran sebesar **{$total}** untuk invoice **{$this->invoice->invoice_number}** sudah kami terima.")
            ->line('Layanan Anda sedang diproses dan akan segera aktif.')
            ->action('Lihat Layanan Saya', route('client.services'))
            ->salutation("Salam,\n{$site}");
    }

    public function toWhatsApp(object $notifiable): string
    {
        $total = 'Rp ' . number_format((float) $this->invoice->total, 0, ',', '.');

        return "Halo {$notifiable->name},\n\n"
            . "Pembayaran *{$total}* untuk invoice *{$this->invoice->invoice_number}* sudah kami terima. Terima kasih!\n\n"
            . "Layanan Anda sedang diproses.";
    }
}
