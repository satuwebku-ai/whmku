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
            'branding_display' => ['nullable', 'in:logo_and_text,logo_only,text_only'],

            'site_logo'        => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:1024'],
            'site_favicon'     => ['nullable', 'image', 'mimes:png,ico,svg', 'max:256'],

            // Masa percobaan hosting -- klien dapat akun cPanel aktif
            // SEBELUM bayar, dengan batas waktu. Kalau tidak dibayar
            // sampai jatuh tempo trial, otomatis disuspend (lihat
            // lumora:suspend-overdue yang sudah menangani ini).
            'trial_enabled'      => ['nullable', 'boolean'],
            'trial_period_days'  => ['required_if:trial_enabled,1', 'nullable', 'integer', 'min:1', 'max:7'],
        ], [
            'theme_color.regex' => 'Warna harus dalam format heksadesimal, contoh #6366F1.',
            'site_logo.max'     => 'Ukuran logo maksimal 1 MB.',
            'site_favicon.max'  => 'Ukuran favicon maksimal 256 KB.',
            'trial_period_days.required_if' => 'Isi berapa hari masa percobaannya (1-7 hari).',
        ]);

        // Logo & favicon disimpan lewat Storage disk 'local'
        // (storage/app/branding) dan dilayani lewat rute Laravel — BUKAN
        // ditulis langsung ke folder public/, karena di beberapa server
        // (termasuk yang pakai cPanel Git Version Control) folder kode
        // yang dieksekusi PHP itu TERPISAH dari folder yang benar-benar
        // dilayani ke publik. Lewat rute Laravel, ini kebal terhadap
        // perbedaan struktur folder apa pun.
        foreach (['site_logo', 'site_favicon'] as $field) {
            unset($data[$field]);

            if ($request->hasFile($field)) {
                $old = Setting::get($field);

                if ($old && Storage::disk('local')->exists('branding/' . $old)) {
                    Storage::disk('local')->delete('branding/' . $old);
                }

                $filename = $field . '_' . time() . '.' . $request->file($field)->getClientOriginalExtension();
                $request->file($field)->storeAs('branding', $filename, 'local');

                $data[$field] = $filename;
            }

            // Centang "hapus" mengosongkan pengaturannya.
            if ($request->boolean('remove_' . $field)) {
                $old = Setting::get($field);

                if ($old && Storage::disk('local')->exists('branding/' . $old)) {
                    Storage::disk('local')->delete('branding/' . $old);
                }

                $data[$field] = null;
            }
        }

        // Checkbox yang tidak dicentang TIDAK terkirim sama sekali di
        // request -- kalau tidak ditangani eksplisit begini, mematikan
        // trial tidak akan pernah benar-benar tersimpan.
        $data['trial_enabled'] = $request->boolean('trial_enabled') ? '1' : '0';

        // Pengaturan trial CUMA boleh diubah Superadmin -- dijaga di sini
        // juga (bukan cuma disembunyikan di tampilan), supaya tidak bisa
        // diakali admin/staff biasa dengan mengirim request langsung.
        if (! auth('admin')->user()->isSuperadmin()) {
            unset($data['trial_enabled'], $data['trial_period_days']);
        }

        Setting::putMany($data, 'general');

        return back()->with('success', 'Pengaturan umum berhasil disimpan.');
    }

    public function seo(): View
    {
        return view('admin.settings.seo');
    }

    /**
     * Alat diagnosa upload logo/favicon.
     */
    public function brandingDiagnostics(): \Illuminate\Http\JsonResponse
    {
        $testContent = 'test-' . time();
        Storage::disk('local')->put('branding/diagnostic-test.txt', $testContent);

        $logo = Setting::get('site_logo');

        return response()->json([
            'metode' => 'Dilayani lewat rute Laravel (bukan file statis) — kebal terhadap folder repository vs folder yang benar-benar dilayani publik.',
            'file_tersimpan_di' => Storage::disk('local')->path('branding/diagnostic-test.txt'),
            'url_untuk_dicoba_manual' => route('branding.file', 'diagnostic-test.txt'),
            'petunjuk' => 'Buka URL di atas langsung di browser. Harus muncul teks "test-....". Kalau masih 404, ada masalah lain (kabari saya, sertakan hasil JSON ini).',
            'logo_tersimpan' => $logo,
            'logo_file_ada' => $logo ? Storage::disk('local')->exists('branding/' . $logo) : null,
            'logo_url' => $logo ? route('branding.file', $logo) : null,
        ], 200, [], JSON_PRETTY_PRINT);
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

        // Status "Terhubung" hasil tes lama tidak lagi valid begitu
        // kredensial WhatsApp diubah — daripada tetap menampilkan tanda
        // sukses palsu untuk pengaturan yang belum pernah diuji ulang.
        if ($request->filled('wa_token') || $request->input('wa_provider') !== Setting::get('wa_provider')
            || $request->input('wa_endpoint') !== Setting::get('wa_endpoint')) {
            Setting::put('wa_last_test_status', null, 'general');
            Setting::put('wa_last_test_at', null, 'general');
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

        // Disimpan supaya status "Terhubung" tetap tampil tiap kali halaman
        // ini dibuka lagi — bukan cuma pesan sekali lewat yang hilang
        // begitu halaman di-refresh.
        Setting::put('wa_last_test_status', $ok ? 'success' : 'failed', 'general');
        Setting::put('wa_last_test_at', now()->toDateTimeString(), 'general');

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

        // Kunci reCAPTCHA yang baru diisi belum tentu benar — status
        // "Success" lama tidak boleh ikut terbawa untuk kunci yang belum
        // pernah diuji ulang.
        if ($request->filled('recaptcha_secret_key')) {
            Setting::put('recaptcha_last_test_status', null, 'security');
        }

        return back()->with('success', 'Pengaturan keamanan berhasil disimpan.');
    }

    /**
     * Uji Secret Key reCAPTCHA LANGSUNG ke API Google — bukan cuma
     * mengecek kolomnya terisi atau tidak. Dikirim tanpa token respons
     * asli (memang tidak mungkin ada, ini pengujian dari admin panel,
     * bukan dari form sungguhan) — tapi kode error yang dikembalikan
     * Google tetap membedakan dengan jelas: "invalid-input-secret"
     * berarti Secret Key-nya salah, sedangkan "missing-input-response"
     * berarti Secret Key-nya diterima Google (cuma tidak ada token
     * karena memang sengaja tidak dikirim).
     */
    public function testRecaptcha(): RedirectResponse
    {
        $secret = Setting::get('recaptcha_secret_key');

        if (blank($secret)) {
            return back()->with('error', 'Isi dulu Secret Key sebelum diuji.');
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => '',
                ]);

            $errorCodes = $response->json('error-codes', []);
            $secretValid = ! in_array('invalid-input-secret', $errorCodes, true)
                && ! in_array('missing-input-secret', $errorCodes, true);

            Setting::put('recaptcha_last_test_status', $secretValid ? 'success' : 'failed', 'security');
            Setting::put('recaptcha_last_test_at', now()->toDateTimeString(), 'security');

            return back()->with(
                $secretValid ? 'success' : 'error',
                $secretValid
                    ? 'Secret Key valid — diterima Google reCAPTCHA.'
                    : 'Secret Key ditolak Google — periksa lagi, kemungkinan salah salin atau untuk versi reCAPTCHA yang berbeda (v2 vs v3).'
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Tidak bisa menghubungi Google reCAPTCHA: ' . $e->getMessage());
        }
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

        // Belum tentu konfigurasi baru itu benar — status "Success" lama
        // tidak boleh ikut terbawa untuk pengaturan yang belum diuji ulang.
        Setting::put('livechat_last_test_status', null, 'livechat');

        return back()->with('success', 'Pengaturan live chat berhasil disimpan.');
    }

    /**
     * Uji live chat sesuai penyedia yang aktif — Tawk.to benar-benar
     * dicek ke server mereka (widget ID yang salah akan 404), sisanya
     * diperiksa formatnya karena tidak ada cara sederhana memverifikasi
     * Crisp/WhatsApp lewat satu panggilan HTTP tanpa memuat JavaScript
     * sungguhan di browser.
     */
    public function testLiveChat(): RedirectResponse
    {
        $provider = Setting::get('livechat_provider', 'none');
        $propertyId = Setting::get('livechat_property_id');
        $whatsapp = Setting::get('livechat_whatsapp');

        [$ok, $message] = match ($provider) {
            'none' => [null, 'Live chat sedang nonaktif — tidak ada yang perlu diuji.'],

            'widget' => [
                filled($whatsapp) || filled(Setting::get('support_email')),
                filled($whatsapp) || filled(Setting::get('support_email'))
                    ? 'Widget Bawaan siap — minimal satu jalur kontak (WhatsApp/email) sudah terisi.'
                    : 'Isi dulu Nomor WhatsApp di sini atau Email Support di Pengaturan Umum.',
            ],

            'whatsapp' => [
                filled($whatsapp) && preg_match('/^[0-9]{9,15}$/', $whatsapp) === 1,
                filled($whatsapp) && preg_match('/^[0-9]{9,15}$/', $whatsapp) === 1
                    ? 'Format nomor WhatsApp valid.'
                    : 'Nomor WhatsApp kosong atau formatnya tidak valid (9–15 digit, diawali kode negara tanpa +).',
            ],

            'tawkto' => $this->testTawkTo($propertyId),

            'crisp' => [
                filled($propertyId) && preg_match('/^[a-f0-9\-]{20,40}$/i', $propertyId) === 1,
                filled($propertyId) && preg_match('/^[a-f0-9\-]{20,40}$/i', $propertyId) === 1
                    ? 'Format Website ID terlihat valid (bentuknya sesuai pola Crisp) — verifikasi penuh cuma bisa lewat tampilan widget sungguhan di halaman publik.'
                    : 'Website ID kosong atau formatnya tidak seperti ID Crisp pada umumnya.',
            ],

            default => [false, 'Penyedia tidak dikenali.'],
        };

        if ($ok !== null) {
            Setting::put('livechat_last_test_status', $ok ? 'success' : 'failed', 'livechat');
            Setting::put('livechat_last_test_at', now()->toDateTimeString(), 'livechat');
        }

        return back()->with($ok === false ? 'error' : 'success', $message);
    }

    private function testTawkTo(?string $propertyId): array
    {
        if (blank($propertyId) || ! str_contains($propertyId, '/')) {
            return [false, 'Property ID kosong atau formatnya salah — harusnya "propertyId/widgetId" (ada tanda garis miring).'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(8)->get("https://embed.tawk.to/{$propertyId}");

            return $response->successful()
                ? [true, 'Widget Tawk.to ditemukan dan aktif.']
                : [false, "Tawk.to mengembalikan status {$response->status()} — Property ID kemungkinan salah atau widget belum dipublikasikan."];
        } catch (\Throwable $e) {
            return [false, 'Tidak bisa menghubungi Tawk.to: ' . $e->getMessage()];
        }
    }
}
