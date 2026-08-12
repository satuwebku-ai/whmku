<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationTemplateController extends Controller
{
    public function index(): View
    {
        $defaults = NotificationTemplate::defaults();
        $customized = NotificationTemplate::pluck('key')->all();

        $templates = collect($defaults)->map(function ($def, $key) use ($customized) {
            $def['key'] = $key;
            $def['is_customized'] = in_array($key, $customized, true);

            return $def;
        })->values();

        return view('admin.notification-templates.index', compact('templates'));
    }

    public function edit(string $key): View
    {
        $defaults = NotificationTemplate::defaults();

        abort_unless(isset($defaults[$key]), 404);

        $effective = NotificationTemplate::effective($key);
        $isCustomized = NotificationTemplate::where('key', $key)->exists();

        return view('admin.notification-templates.form', [
            'key' => $key,
            'meta' => $defaults[$key],
            'effective' => $effective,
            'isCustomized' => $isCustomized,
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $defaults = NotificationTemplate::defaults();

        abort_unless(isset($defaults[$key]), 404);

        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body_mail' => ['nullable', 'string', 'max:10000'],
            'body_whatsapp' => ['nullable', 'string', 'max:5000'],
        ]);

        // Marker wajib ([RINCIAN], [DAFTAR_LAYANAN], [ISI_PROMO]) untuk
        // template pembungkus — tanpa ini, bagian dinamis (kredensial,
        // isi promo, dll) akan hilang tak tersisipkan sama sekali.
        $requiredMarker = match ($key) {
            'admin_alert' => '[RINCIAN]',
            'order_provisioned' => '[DAFTAR_LAYANAN]',
            'promo_broadcast' => '[ISI_PROMO]',
            default => null,
        };

        if ($requiredMarker && filled($data['body_mail'] ?? null) && ! str_contains($data['body_mail'], $requiredMarker)) {
            return back()->withInput()->with('error', "Isi email wajib menyertakan tanda {$requiredMarker} — itu tempat sistem menyisipkan bagian yang berbeda tiap kejadian.");
        }

        NotificationTemplate::updateOrCreate(['key' => $key], $data);

        return redirect()->route('admin.notification-templates.edit', $key)->with('success', 'Template berhasil disimpan.');
    }

    public function reset(string $key): RedirectResponse
    {
        NotificationTemplate::where('key', $key)->delete();

        return back()->with('success', 'Template dikembalikan ke kata-kata bawaan.');
    }
}
