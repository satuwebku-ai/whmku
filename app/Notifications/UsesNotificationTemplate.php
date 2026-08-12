<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Ubah teks template tersimpan (baris per baris, dipisah "\n") jadi
 * pemanggilan ->line()/->action() sungguhan di MailMessage.
 *
 * Baris berbentuk "[ACTION:Label Tombol:https://...]" diubah jadi tombol
 * aksi, baris lain diperlakukan sebagai paragraf biasa — supaya admin
 * bisa menaruh tombol di tengah isi email lewat teks biasa di form,
 * tanpa perlu tahu soal kode.
 */
trait UsesNotificationTemplate
{
    protected function applyTemplateBody(MailMessage $mail, string $body): MailMessage
    {
        foreach (explode("\n", $body) as $line) {
            $line = rtrim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^\[ACTION:(.+?):(.+)\]$/', $line, $m)) {
                $mail->action(trim($m[1]), trim($m[2]));
                continue;
            }

            $mail->line($line);
        }

        return $mail;
    }
}
