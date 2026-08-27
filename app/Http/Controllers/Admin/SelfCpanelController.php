<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Setting;
use App\Services\Hosting\CpanelWhmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Login sekali klik ke cPanel server tempat APLIKASI LUMORA INI
 * SENDIRI berjalan -- beda dari SSO client (yang login ke cPanel
 * LAYANAN klien). Ini untuk keperluan admin: cek file, log, database,
 * dst di hosting Lumora sendiri, tanpa perlu ingat password cPanel
 * setiap kali.
 *
 * Dipakai ulang PERSIS mekanisme SSO yang sudah terbukti jalan untuk
 * client hosting account (CpanelWhmService::createSsoSession) --
 * bedanya cuma username & server tujuannya diambil dari pengaturan
 * yang admin pilih sendiri di sini, bukan dari data hosting_account.
 */
class SelfCpanelController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.self-cpanel', [
            'servers' => Server::where('panel', 'cpanel')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'self_cpanel_server_id' => ['required', 'exists:servers,id'],
            'self_cpanel_username' => ['required', 'string', 'max:50'],
        ]);

        Setting::putMany($data, 'general');

        return back()->with('success', 'Pengaturan cPanel aplikasi berhasil disimpan.');
    }

    /**
     * Buat sesi SSO lalu langsung alihkan ke cPanel -- tautan berlaku
     * sekali pakai, kedaluwarsa beberapa menit setelah dibuat (sama
     * seperti SSO client).
     */
    public function login(): RedirectResponse
    {
        $serverId = Setting::get('self_cpanel_server_id');
        $username = Setting::get('self_cpanel_username');

        if (! $serverId || ! $username) {
            return redirect()->route('admin.self-cpanel.edit')
                ->with('error', 'Atur dulu server & username cPanel aplikasi ini sebelum bisa login sekali klik.');
        }

        $server = Server::find($serverId);

        if (! $server) {
            return redirect()->route('admin.self-cpanel.edit')
                ->with('error', 'Server yang dipilih sebelumnya sudah tidak ada — pilih ulang.');
        }

        $service = new CpanelWhmService($server);
        $result = $service->createSsoSession($username);

        if (! $result['success'] || empty($result['url'])) {
            return redirect()->route('admin.self-cpanel.edit')
                ->with('error', 'Gagal membuat sesi login: ' . $result['message']);
        }

        return redirect()->away($result['url']);
    }
}
