<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendPasswordResetCode extends Notification
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
            ->subject('Kode Reset Password — ' . config('app.name'))
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun Anda. Gunakan kode berikut:')
            ->line('**' . $this->code . '**')
            ->line('Kode berlaku 15 menit dan hanya bisa dipakai sekali.')
            ->line('Kalau Anda tidak meminta reset password, abaikan email ini — password Anda tidak berubah.')
            ->salutation('Terima kasih.');
    }
}
