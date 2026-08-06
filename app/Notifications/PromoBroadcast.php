<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email/WA promosi. Satu-satunya notifikasi yang bisa ditolak klien —
 * karena itu ditandai isPromotional.
 */
class PromoBroadcast extends Notification
{
    use Queueable, ResolvesChannels;

    protected bool $isPromotional = true;

    public function __construct(
        public string $judul,
        public string $isi,
        public ?string $tautan = null,
        public ?string $labelTautan = null,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_name', config('app.name'));

        $mail = (new MailMessage)
            ->subject($this->judul)
            ->greeting("Halo {$notifiable->name},");

        foreach (preg_split('/\n{2,}/', trim($this->isi)) as $paragraf) {
            $mail->line($paragraf);
        }

        if ($this->tautan) {
            $mail->action($this->labelTautan ?: 'Selengkapnya', $this->tautan);
        }

        return $mail
            ->line('---')
            ->line('Tidak ingin menerima email promosi? Matikan lewat menu Profil Saya di halaman klien.')
            ->salutation("Salam,\n{$site}");
    }

    public function toWhatsApp(object $notifiable): string
    {
        $pesan = "*{$this->judul}*\n\n" . trim($this->isi);

        if ($this->tautan) {
            $pesan .= "\n\n" . $this->tautan;
        }

        return $pesan;
    }
}
