<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Services\Domain\DomainRegistrarFactory;
use App\Services\Hosting\HostingPanelFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function services(Request $request): View
    {
        $services = Auth::guard('client')->user()
            ->hostingAccounts()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.services.index', compact('services'));
    }

    public function service(HostingAccount $service): View
    {
        // Pastikan layanan ini benar-benar milik klien yang sedang login.
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        $service->load('orders');

        return view('client.services.show', compact('service'));
    }

    public function domains(Request $request): View
    {
        $domains = Auth::guard('client')->user()
            ->domains()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.domains.index', compact('domains'));
    }

    public function domain(Domain $domain): View
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        return view('client.domains.show', compact('domain'));
    }

    /**
     * Login sekali klik ke cPanel.
     *
     * Server membuat sesi berisi token sekali pakai, lalu klien langsung
     * diarahkan ke sana — tidak perlu tahu password akun cPanel-nya.
     */
    public function loginPanel(HostingAccount $service): RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        if ($service->status !== 'active') {
            return back()->with('error', 'Layanan ini sedang tidak aktif, jadi belum bisa diakses.');
        }

        if (! $service->serverModel || ! $service->username) {
            return back()->with('error', 'Layanan ini belum terhubung ke server. Silakan hubungi support.');
        }

        $panel = HostingPanelFactory::make($service->serverModel);

        if (! method_exists($panel, 'createSsoSession')) {
            return back()->with('error', 'Panel ' . $service->serverModel->panel . ' belum mendukung login sekali klik.');
        }

        $result = $panel->createSsoSession($service->username);

        if (! $result['success']) {
            return back()->with('error', 'Gagal membuat sesi login: ' . $result['message']);
        }

        // away() dipakai karena tujuannya di luar aplikasi ini.
        return redirect()->away($result['url']);
    }

    /**
     * Ubah nameserver domain lewat API registrar.
     */
    public function updateNameservers(Request $request, Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        $data = $request->validate([
            'nameservers'   => ['required', 'array', 'min:2', 'max:5'],
            'nameservers.*' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'],
        ], [
            'nameservers.min' => 'Minimal dua nameserver harus diisi.',
            'nameservers.*.regex' => 'Format nameserver tidak valid. Contoh: ns1.contoh.com',
        ]);

        // Buang baris kosong, lalu pastikan tetap ada minimal dua.
        $nameservers = array_values(array_filter(
            array_map('trim', $data['nameservers']),
            fn ($ns) => $ns !== ''
        ));

        if (count($nameservers) < 2) {
            return back()->with('error', 'Minimal dua nameserver harus diisi.');
        }

        if ($domain->status !== 'active') {
            return back()->with('error', 'Nameserver hanya bisa diubah untuk domain yang aktif.');
        }

        if (! $domain->registrar) {
            return back()->with('error', 'Domain ini tidak terhubung ke registrar. Silakan hubungi support.');
        }

        $result = DomainRegistrarFactory::make($domain->registrar)
            ->setNameservers($domain->domain_name, $nameservers);

        if (! $result['success']) {
            return back()->with('error', 'Gagal mengubah nameserver: ' . $result['message']);
        }

        $domain->update(['nameservers' => $nameservers]);

        return back()->with('success', 'Nameserver berhasil diperbarui. Perubahan DNS bisa memakan waktu hingga 24 jam untuk menyebar.');
    }

    /**
     * Klien menyalakan/mematikan perpanjangan otomatis domainnya sendiri.
     * Sebelumnya kolom ini hanya bisa dilihat, tidak bisa diubah klien —
     * satu-satunya jalan adalah menghubungi support, padahal ini murni
     * preferensi klien sendiri, tidak ada alasan untuk melibatkan admin.
     */
    public function toggleDomainAutoRenew(Domain $domain): RedirectResponse
    {
        abort_unless($domain->client_id === Auth::guard('client')->id(), 403);

        $domain->update(['auto_renew' => ! $domain->auto_renew]);

        return back()->with('success', $domain->auto_renew
            ? 'Perpanjangan otomatis diaktifkan. Invoice akan dibuat otomatis mendekati tanggal kedaluwarsa.'
            : 'Perpanjangan otomatis dimatikan. Anda perlu memperpanjang domain secara manual sebelum kedaluwarsa.');
    }

    /**
     * Ajukan pembatalan layanan — belum menghentikan apapun, hanya masuk
     * antrean tinjauan admin. Ini disengaja: pembatalan otomatis berisiko
     * mematikan layanan yang masih dibutuhkan hanya karena klik yang salah
     * atau permintaan yang berubah pikiran.
     */
    public function requestCancellation(Request $request, HostingAccount $service): RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        if ($service->hasPendingCancellation()) {
            return back()->with('error', 'Sudah ada pengajuan pembatalan yang sedang ditinjau untuk layanan ini.');
        }

        if ($service->status === 'terminated') {
            return back()->with('error', 'Layanan ini sudah tidak aktif.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->update([
            'cancellation_status' => 'requested',
            'cancellation_reason' => $data['reason'],
            'cancellation_requested_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan pembatalan berhasil dikirim. Tim kami akan meninjau dalam 1x24 jam.');
    }

    /**
     * Batalkan pengajuan pembatalan yang belum diproses admin.
     */
    public function withdrawCancellation(HostingAccount $service): RedirectResponse
    {
        abort_unless($service->client_id === Auth::guard('client')->id(), 403);

        if (! $service->hasPendingCancellation()) {
            return back()->with('error', 'Tidak ada pengajuan pembatalan yang aktif.');
        }

        $service->update([
            'cancellation_status' => 'none',
            'cancellation_reason' => null,
            'cancellation_requested_at' => null,
        ]);

        return back()->with('success', 'Pengajuan pembatalan dibatalkan.');
    }
}
