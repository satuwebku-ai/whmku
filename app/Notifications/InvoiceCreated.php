<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoiceCreated extends Notification
{
    use Queueable, ResolvesChannels;

    public function __construct(public Invoice $invoice) {}

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_name', config('app.name'));
        $total = 'Rp ' . number_format((float) $this->invoice->total, 0, ',', '.');

        $mail = (new MailMessage)
            ->subject("Invoice {$this->invoice->invoice_number} — {$site}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Berikut tagihan untuk pesanan Anda.")
            ->line("**Nomor Invoice:** {$this->invoice->invoice_number}")
            ->line("**Total:** {$total}")
            ->line("**Jatuh tempo:** " . $this->invoice->due_date->format('d M Y'))
            ->action('Lihat & Bayar Invoice', route('client.invoices.show', $this->invoice))
            ->line('Layanan akan otomatis aktif setelah pembayaran kami terima.');

        // PDF dilampirkan kalau bisa dibuat. Kegagalan membuat PDF tidak
        // boleh membatalkan pengiriman email tagihannya.
        try {
            $this->invoice->loadMissing(['items', 'client', 'coupon']);

            $pdf = Pdf::loadView('client.invoices.pdf', ['invoice' => $this->invoice])->setPaper('a4');

            $mail->attachData(
                $pdf->output(),
                "Invoice-{$this->invoice->invoice_number}.pdf",
                ['mime' => 'application/pdf']
            );
        } catch (Throwable $e) {
            Log::warning('Gagal melampirkan PDF invoice: ' . $e->getMessage(), [
                'invoice_id' => $this->invoice->id,
            ]);
        }

        return $mail->salutation("Salam,\n{$site}");
    }

    public function toWhatsApp(object $notifiable): string
    {
        $total = 'Rp ' . number_format((float) $this->invoice->total, 0, ',', '.');

        return "Halo {$notifiable->name},\n\n"
            . "Invoice *{$this->invoice->invoice_number}* sebesar *{$total}* sudah terbit.\n"
            . "Jatuh tempo: " . $this->invoice->due_date->format('d M Y') . "\n\n"
            . "Bayar di sini:\n" . route('client.invoices.show', $this->invoice);
    }
}
