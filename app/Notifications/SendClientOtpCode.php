<?php

namespace App\Notifications;

use App\Models\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Terpisah dari SendOtpCode (khusus admin) karena template bawaannya
 * secara eksplisit menyebut "admin panel" di teksnya -- tidak cocok
 * dipakai ulang untuk login klien. Logika & struktur sama persis,
 * cuma template key & kata-kata defaultnya yang beda.
 */
class SendClientOtpCode extends Notification
{
    use Queueable, UsesNotificationTemplate;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = ['client_name' => $notifiable->name, 'site_name' => config('app.name'), 'code' => $this->code];
        $tpl = NotificationTemplate::effective('send_client_otp_code');

        $mail = (new MailMessage)
            ->subject(NotificationTemplate::substitute($tpl['subject'], $data))
            ->greeting("Halo {$notifiable->name},");

        $this->applyTemplateBody($mail, NotificationTemplate::substitute($tpl['body_mail'], $data));

        return $mail->salutation('Terima kasih.');
    }
}
