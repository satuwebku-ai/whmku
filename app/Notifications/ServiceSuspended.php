<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceSuspended extends Notification
{
    use Queueable, ResolvesChannels;

    public function __construct(
        public string $serviceLabel, // nama domain / layanan yang disuspend
        public Invoice $invoice,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_name', config('app.name'));
        $total = 'Rp ' . number_format((float) $this->invoice->total, 0, ',', '.');

        return (new MailMessage)
            ->subject("Layanan Disuspend — {$this->serviceLabel}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Layanan **{$this->serviceLabel}** telah disuspend sementara karena tagihan **{$this->invoice->invoice_number}** ({$total}) belum dibayar hingga melewati batas waktu.")
            ->line('Layanan akan aktif kembali secara otomatis begitu pembayaran kami terima — tidak perlu menghubungi kami untuk mengaktifkan ulang.')
            ->action('Bayar Sekarang', route('client.invoices.show', $this->invoice))
            ->line('Kalau ada kendala, balas email ini atau buat tiket support.')
            ->salutation("Salam,\n{$site}");
    }

    public function toWhatsApp(object $notifiable): string
    {
        $total = 'Rp ' . number_format((float) $this->invoice->total, 0, ',', '.');

        return "Halo {$notifiable->name},\n\n"
            . "Layanan *{$this->serviceLabel}* disuspend sementara karena tagihan *{$this->invoice->invoice_number}* ({$total}) belum dibayar.\n\n"
            . "Bayar di sini untuk aktif kembali otomatis:\n" . route('client.invoices.show', $this->invoice);
    }
}
