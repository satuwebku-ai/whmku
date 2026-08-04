<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpCode extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Login — ' . config('app.name'))
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Berikut kode verifikasi untuk melanjutkan login ke admin panel:')
            ->line('**' . $this->code . '**')
            ->line('Kode ini berlaku 10 menit dan hanya bisa dipakai sekali.')
            ->line('Kalau Anda tidak sedang mencoba login, segera ganti password akun Anda — ada kemungkinan orang lain mengetahui kredensial Anda.')
            ->salutation('Terima kasih.');
    }
}
