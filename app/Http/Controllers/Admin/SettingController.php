<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function general(): View
    {
        return view('admin.settings.general');
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name'        => ['required', 'string', 'max:120'],
            'site_tagline'     => ['nullable', 'string', 'max:255'],
            'company_name'     => ['nullable', 'string', 'max:255'],
            'support_email'    => ['nullable', 'email', 'max:255'],
            'support_phone'    => ['nullable', 'string', 'max:50'],
            'company_address'  => ['nullable', 'string', 'max:500'],
            'footer_text'      => ['nullable', 'string', 'max:500'],
            'theme_color'      => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'site_logo'        => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:1024'],
            'site_favicon'     => ['nullable', 'image', 'mimes:png,ico,svg', 'max:256'],
        ], [
            'theme_color.regex' => 'Warna harus dalam format heksadesimal, contoh #6366F1.',
            'site_logo.max'     => 'Ukuran logo maksimal 1 MB.',
            'site_favicon.max'  => 'Ukuran favicon maksimal 256 KB.',
        ]);

        // Logo & favicon SENGAJA disimpan LANGSUNG di public/uploads/branding
        // (bukan storage/app/public seperti berkas lain) — dua aset ini
        // harus tampil ke SEMUA pengunjung tanpa login, dan banyak hosting
        // berbagi (kemungkinan besar termasuk punyamu) mematikan symlink
        // `public/storage` demi keamanan. Kalau tetap dipaksa lewat
        // storage/app/public, filenya tersimpan tapi URL-nya 404 selamanya
        // — persis yang terjadi sebelum perbaikan ini.
        foreach (['site_logo', 'site_favicon'] as $field) {
            unset($data[$field]);

            if ($request->hasFile($field)) {
                $old = Setting::get($field);

                if ($old && file_exists(public_path('uploads/branding/' . $old))) {
                    @unlink(public_path('uploads/branding/' . $old));
                }

                $destination = public_path('uploads/branding');

                if (! is_dir($destination)) {
                    mkdir($destination, 0755, true);
                }

                $filename = $field . '_' . time() . '.' . $request->file($field)->getClientOriginalExtension();
                $request->file($field)->move($destination, $filename);

                $data[$field] = $filename;
            }

            // Centang "hapus" mengosongkan pengaturannya.
            if ($request->boolean('remove_' . $field)) {
                $old = Setting::get($field);

                if ($old && file_exists(public_path('uploads/branding/' . $old))) {
                    @unlink(public_path('uploads/branding/' . $old));
                }

                $data[$field] = null;
            }
        }

        Setting::putMany($data, 'general');

        return back()->with('success', 'Pengaturan umum berhasil disimpan.');
    }

    public function seo(): View
    {
        return view('admin.settings.seo');
    }

    public function updateSeo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'seo_title'        => ['nullable', 'string', 'max:70'],
            'seo_description'  => ['nullable', 'string', 'max:170'],
            'seo_keywords'     => ['nullable', 'string', 'max:255'],
            'seo_og_image'     => ['nullable', 'string', 'max:255'],
            'seo_canonical'    => ['nullable', 'url', 'max:255'],
            'seo_robots'       => ['nullable', 'string', 'max:2000'],
            'seo_noindex_site' => ['nullable', 'boolean'],
        ]);

        $data['seo_noindex_site'] = $request->boolean('seo_noindex_site') ? '1' : '0';

        Setting::putMany($data, 'seo');

        return back()->with('success', 'Pengaturan SEO berhasil disimpan.');
    }

    public function analytics(): View
    {
        return view('admin.settings.analytics');
    }

    public function updateAnalytics(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Hanya ID, bukan potongan script — supaya tidak jadi celah XSS.
            'ga_measurement_id' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]*$/'],
            'gtm_container_id'  => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]*$/'],
            'fb_pixel_id'       => ['nullable', 'string', 'max:50', 'regex:/^[0-9]*$/'],
        ], [
            'ga_measurement_id.regex' => 'Isi ID-nya saja (contoh: G-XXXXXXX), bukan seluruh kode script.',
            'gtm_container_id.regex'  => 'Isi ID-nya saja (contoh: GTM-XXXXXX), bukan seluruh kode script.',
            'fb_pixel_id.regex'       => 'Facebook Pixel ID hanya berisi angka.',
        ]);

        Setting::putMany($data, 'analytics');

        return back()->with('success', 'Pengaturan analytics berhasil disimpan.');
    }


    public function notifications(): View
    {
        return view('admin.settings.notifications');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Notifikasi ke klien
            'notify_welcome'   => ['nullable', 'boolean'],
            'notify_invoice'   => ['nullable', 'boolean'],
            'notify_paid'      => ['nullable', 'boolean'],
            'notify_reminder'  => ['nullable', 'boolean'],

            // Notifikasi ke admin
            'notify_admin_order'   => ['nullable', 'boolean'],
            'notify_admin_payment' => ['nullable', 'boolean'],
            'notify_admin_ticket'  => ['nullable', 'boolean'],
            'notify_admin_client'  => ['nullable', 'boolean'],

            // Jadwal pengingat
            'reminder_days_before' => ['nullable', 'string', 'regex:/^[0-9,\s]*$/'],
            'reminder_days_after'  => ['nullable', 'string', 'regex:/^[0-9,\s]*$/'],
            'renewal_invoice_days_before' => ['nullable', 'integer', 'min:1', 'max:60'],
            'auto_suspend_enabled' => ['nullable', 'boolean'],
            'suspend_grace_days'   => ['nullable', 'integer', 'min:0', 'max:30'],
            'notify_suspend'       => ['nullable', 'boolean'],

            // WhatsApp
            'wa_provider' => ['required', 'in:none,fonnte,wablas,custom'],
            'wa_token'    => ['nullable', 'string', 'max:500'],
            'wa_endpoint' => ['nullable', 'string', 'max:255'],
            'wa_admin_number' => ['nullable', 'string', 'max:30'],
        ], [
            'reminder_days_before.regex' => 'Isi angka dipisah koma, contoh: 7,3,1',
            'reminder_days_after.regex'  => 'Isi angka dipisah koma, contoh: 1,7',
        ]);

        // Checkbox yang tidak dicentang tidak ikut terkirim, jadi diisi
        // eksplisit agar nilainya benar-benar tersimpan sebagai "mati".
        foreach ([
            'notify_welcome', 'notify_invoice', 'notify_paid', 'notify_reminder',
            'notify_admin_order', 'notify_admin_payment', 'notify_admin_ticket', 'notify_admin_client',
            'auto_suspend_enabled', 'notify_suspend',
        ] as $toggle) {
            $data[$toggle] = $request->boolean($toggle) ? '1' : '0';
        }

        // Token kosong saat sudah ada nilai = tidak diganti.
        if (blank($data['wa_token'] ?? null)) {
            unset($data['wa_token']);
        }

        Setting::putMany($data, 'notification');

        return back()->with('success', 'Pengaturan notifikasi berhasil disimpan.');
    }

    /**
     * Kirim WhatsApp percobaan untuk memastikan gateway sudah benar.
     */
    public function testWhatsApp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'test_number' => ['required', 'string', 'max:30'],
        ]);

        $site = Setting::get('site_name', config('app.name'));

        $ok = app(\App\Notifications\Channels\WhatsAppChannel::class)->dispatch(
            $data['test_number'],
            "Tes notifikasi WhatsApp dari {$site}.\n\nKalau pesan ini sampai, berarti gateway sudah tersambung dengan benar."
        );

        return back()->with(
            $ok ? 'success' : 'error',
            $ok
                ? 'Pesan percobaan terkirim. Cek WhatsApp di nomor tersebut.'
                : 'Gagal mengirim. Periksa provider, token, dan endpoint — detailnya ada di storage/logs/laravel.log.'
        );
    }

    public function security(): View
    {
        return view('admin.settings.security');
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'captcha_mode' => ['required', 'in:off,adaptive,always'],
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:255'],
            'require_email_verification' => ['nullable', 'boolean'],
        ]);

        $data['require_email_verification'] = $request->boolean('require_email_verification') ? '1' : '0';

        if (blank($data['recaptcha_secret_key'] ?? null)) {
            unset($data['recaptcha_secret_key']);
        }

        Setting::putMany($data, 'security');

        return back()->with('success', 'Pengaturan keamanan berhasil disimpan.');
    }

    public function livechat(): View
    {
        return view('admin.settings.livechat');
    }

    public function updateLivechat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'livechat_provider'   => ['required', 'in:none,widget,tawkto,crisp,whatsapp'],
            'livechat_property_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9\/_\-]*$/'],
            'livechat_whatsapp'   => ['nullable', 'string', 'max:30', 'regex:/^[0-9]*$/'],
            'livechat_greeting'   => ['nullable', 'string', 'max:255'],
            'support_hours'       => ['nullable', 'string', 'max:120'],
            'chat_greeting_1'     => ['nullable', 'string', 'max:300'],
            'chat_greeting_2'     => ['nullable', 'string', 'max:300'],
        ], [
            'livechat_property_id.regex' => 'Isi ID widget saja, bukan seluruh kode script.',
            'livechat_whatsapp.regex'    => 'Nomor WhatsApp hanya berisi angka, diawali kode negara. Contoh: 6281234567890',
        ]);

        Setting::putMany($data, 'livechat');

        return back()->with('success', 'Pengaturan live chat berhasil disimpan.');
    }
}
