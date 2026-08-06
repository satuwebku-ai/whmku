<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Notifications\Channels\WhatsAppChannel;

/**
 * Menentukan lewat channel mana sebuah notifikasi dikirim.
 *
 * Aturannya:
 *  - Email transaksional (invoice, tagihan, tiket, layanan aktif) SELALU
 *    dikirim. Ini bagian dari layanan, bukan promosi — mematikannya akan
 *    membuat pelanggan tidak tahu tagihannya.
 *  - Email promosi hanya kalau klien tidak menolaknya.
 *  - WhatsApp hanya kalau (a) gateway diaktifkan admin, DAN (b) klien
 *    sendiri mengaktifkannya di profil. Dua-duanya harus setuju.
 */
trait ResolvesChannels
{
    /**
     * Apakah notifikasi ini bersifat promosi? Ditimpa di kelas promo.
     */
    protected bool $isPromotional = false;

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->wantsEmail($notifiable)) {
            $channels[] = 'mail';
        }

        if ($this->wantsWhatsApp($notifiable)) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    private function wantsEmail(object $notifiable): bool
    {
        if (blank($notifiable->email ?? null)) {
            return false;
        }

        // Klien bisa menolak email promosi, tapi tidak email transaksional.
        if ($this->isPromotional && isset($notifiable->notify_promo)) {
            return (bool) $notifiable->notify_promo;
        }

        return true;
    }

    private function wantsWhatsApp(object $notifiable): bool
    {
        // Gateway belum diatur admin — tidak ada yang bisa dikirim.
        if (Setting::get('wa_provider', 'none') === 'none' || blank(Setting::get('wa_token'))) {
            return false;
        }

        if (! method_exists($this, 'toWhatsApp')) {
            return false;
        }

        $number = $notifiable->whatsapp_number ?? $notifiable->phone ?? null;

        if (blank($number)) {
            return false;
        }

        // Admin (model Admin) tidak punya kolom opt-in; keputusannya ada
        // di pengaturan global.
        if (! isset($notifiable->notify_whatsapp)) {
            return true;
        }

        return (bool) $notifiable->notify_whatsapp;
    }
}
