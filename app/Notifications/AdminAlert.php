<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan ke admin untuk kejadian penting: pesanan masuk,
 * pembayaran diterima, tiket baru, klien mendaftar, dan sebagainya.
 *
 * Dibuat satu kelas generik alih-alih satu kelas per kejadian, karena
 * isinya hanya berbeda di judul/rincian — memecahnya jadi belasan kelas
 * hanya menambah berkas tanpa menambah kemampuan.
 */
class AdminAlert extends Notification
{
    use Queueable, ResolvesChannels;

    /**
     * @param  array<string, string>  $details  baris rincian: label => nilai
     */
    public function __construct(
        public string $judul,
        public array $details = [],
        public ?string $tautan = null,
        public string $level = 'info',
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::get('site_name', config('app.name'));

        $mail = (new MailMessage)
            ->subject("[{$site}] {$this->judul}")
            ->greeting("Halo {$notifiable->name},")
            ->line($this->judul);

        foreach ($this->details as $label => $nilai) {
            $mail->line("**{$label}:** {$nilai}");
        }

        if ($this->tautan) {
            $mail->action('Buka di Admin Panel', $this->tautan);
        }

        return $mail->salutation("Notifikasi otomatis dari {$site}");
    }

    public function toWhatsApp(object $notifiable): string
    {
        $pesan = "*{$this->judul}*\n";

        foreach ($this->details as $label => $nilai) {
            $pesan .= "\n{$label}: {$nilai}";
        }

        if ($this->tautan) {
            $pesan .= "\n\n" . $this->tautan;
        }

        return $pesan;
    }
}
