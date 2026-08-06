<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pengingat tagihan. Dipakai untuk dua situasi:
 *  - sebelum jatuh tempo (H-n)
 *  - setelah lewat jatuh tempo
 *
 * Nadanya dibedakan supaya pengingat pertama tidak terasa menuduh.
 */
class InvoiceDueReminder extends Notification
{
    use Queueable, ResolvesChannels;

    public function __construct(
        public Invoice $invoice,
        public int $daysLeft, // negatif = sudah lewat jatuh tempo
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_name', config('app.name'));
        $total = 'Rp ' . number_format((float) $this->invoice->total, 0, ',', '.');
        $overdue = $this->daysLeft < 0;

        $mail = (new MailMessage)
            ->subject(($overdue ? 'Tagihan Terlambat' : 'Pengingat Tagihan') . " — {$this->invoice->invoice_number}")
            ->greeting("Halo {$notifiable->name},");

        if ($overdue) {
            $mail->line("Tagihan **{$this->invoice->invoice_number}** sebesar **{$total}** sudah melewati jatuh tempo " . abs($this->daysLeft) . " hari.")
                 ->line('Mohon segera diselesaikan agar layanan Anda tetap berjalan.');
        } else {
            $mail->line("Tagihan **{$this->invoice->invoice_number}** sebesar **{$total}** akan jatuh tempo dalam **{$this->daysLeft} hari** (" . $this->invoice->due_date->format('d M Y') . ").")
                 ->line('Kalau sudah dibayar, abaikan email ini.');
        }

        return $mail
            ->action('Bayar Sekarang', route('client.invoices.show', $this->invoice))
            ->salutation("Salam,\n{$site}");
    }

    public function toWhatsApp(object $notifiable): string
    {
        $total = 'Rp ' . number_format((float) $this->invoice->total, 0, ',', '.');

        if ($this->daysLeft < 0) {
            return "Halo {$notifiable->name},\n\n"
                . "Tagihan *{$this->invoice->invoice_number}* ({$total}) sudah lewat jatuh tempo "
                . abs($this->daysLeft) . " hari.\n\nBayar di sini:\n"
                . route('client.invoices.show', $this->invoice);
        }

        return "Halo {$notifiable->name},\n\n"
            . "Pengingat: tagihan *{$this->invoice->invoice_number}* ({$total}) jatuh tempo dalam {$this->daysLeft} hari.\n\n"
            . route('client.invoices.show', $this->invoice);
    }
}
