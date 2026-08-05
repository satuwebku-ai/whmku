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

        // Berkas diproses terpisah: yang disimpan di settings hanya path-nya.
        foreach (['site_logo', 'site_favicon'] as $field) {
            unset($data[$field]);

            if ($request->hasFile($field)) {
                // Hapus berkas lama supaya storage tidak menumpuk.
                $old = Setting::get($field);

                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }

                $data[$field] = $request->file($field)->store('branding', 'public');
            }

            // Centang "hapus" mengosongkan pengaturannya.
            if ($request->boolean('remove_' . $field)) {
                $old = Setting::get($field);

                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
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

    public function livechat(): View
    {
        return view('admin.settings.livechat');
    }

    public function updateLivechat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'livechat_provider'   => ['required', 'in:none,tawkto,crisp,whatsapp'],
            'livechat_property_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9\/_\-]*$/'],
            'livechat_whatsapp'   => ['nullable', 'string', 'max:30', 'regex:/^[0-9]*$/'],
            'livechat_greeting'   => ['nullable', 'string', 'max:255'],
        ], [
            'livechat_property_id.regex' => 'Isi ID widget saja, bukan seluruh kode script.',
            'livechat_whatsapp.regex'    => 'Nomor WhatsApp hanya berisi angka, diawali kode negara. Contoh: 6281234567890',
        ]);

        Setting::putMany($data, 'livechat');

        return back()->with('success', 'Pengaturan live chat berhasil disimpan.');
    }
}
