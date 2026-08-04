<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registrar;
use App\Models\Tld;
use App\Services\Domain\DomainRegistrarFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrarController extends Controller
{
    public function index(): View
    {
        $registrars = Registrar::withCount(['tlds', 'domains'])->latest()->paginate(10);

        return view('admin.registrars.index', compact('registrars'));
    }

    public function create(): View
    {
        return view('admin.registrars.form', ['registrar' => new Registrar()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sandbox'] = $request->boolean('sandbox', true);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            Registrar::query()->update(['is_default' => false]);
        }

        Registrar::create($data);

        return redirect()->route('admin.registrars.index')->with('success', 'Registrar berhasil ditambahkan.');
    }

    public function edit(Registrar $registrar): View
    {
        return view('admin.registrars.form', compact('registrar'));
    }

    public function update(Request $request, Registrar $registrar): RedirectResponse
    {
        $data = $this->validated($request, updating: true);
        $data['sandbox'] = $request->boolean('sandbox');
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        if ($data['is_default']) {
            Registrar::query()->where('id', '!=', $registrar->id)->update(['is_default' => false]);
        }

        $registrar->update($data);

        return redirect()->route('admin.registrars.index')->with('success', 'Registrar berhasil diperbarui.');
    }

    public function destroy(Registrar $registrar): RedirectResponse
    {
        if ($registrar->domains()->exists() || $registrar->tlds()->exists()) {
            return back()->with('error', 'Registrar tidak bisa dihapus karena masih dipakai oleh TLD/domain.');
        }

        $registrar->delete();

        return redirect()->route('admin.registrars.index')->with('success', 'Registrar berhasil dihapus.');
    }

    public function testConnection(Registrar $registrar): RedirectResponse
    {
        $result = DomainRegistrarFactory::make($registrar)->testConnection();

        $registrar->update([
            'last_checked_at' => now(),
            'last_check_status' => $result['success'] ? 'ok' : $result['message'],
        ]);

        $label = ['namecheap' => 'Namecheap', 'liquid' => 'Liqu.id', 'resellbiz' => 'ResellBiz'][$registrar->provider] ?? $registrar->provider;

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? "Koneksi ke {$label} berhasil." : 'Koneksi gagal: ' . $result['message']
        );
    }

    /**
     * Impor daftar TLD dari registrar ke tabel TLD Pricing.
     *
     * Harga jual TIDAK ditimpa kalau TLD-nya sudah ada — supaya markup
     * yang sudah kamu atur tidak hilang saat sinkronisasi ulang.
     */
    public function syncTlds(Registrar $registrar): RedirectResponse
    {
        $service = DomainRegistrarFactory::make($registrar);

        if (! method_exists($service, 'listTlds')) {
            return back()->with('error', 'Provider ini belum mendukung sinkronisasi TLD otomatis.');
        }

        $result = $service->listTlds();

        if (! $result['success']) {
            return back()->with('error', 'Gagal mengambil daftar TLD: ' . $result['message']);
        }

        $created = 0;
        $skipped = 0;

        foreach ($result['tlds'] as $row) {
            $existing = Tld::where('extension', $row['extension'])->first();

            if ($existing) {
                // Hubungkan ke registrar ini kalau belum punya, tapi jangan
                // sentuh harganya.
                if (! $existing->registrar_id) {
                    $existing->update(['registrar_id' => $registrar->id]);
                }
                $skipped++;
                continue;
            }

            // Harga modal dari registrar dipakai sebagai nilai awal.
            // Markup jual silakan diatur sendiri di TLD Pricing.
            $cost = $row['price'] !== null ? (float) $row['price'] : 0;

            Tld::create([
                'extension' => $row['extension'],
                'registrar_id' => $registrar->id,
                'register_price' => $cost,
                'renew_price' => $cost,
                'transfer_price' => $cost,
                'min_years' => 1,
                'max_years' => 10,
                // Dinonaktifkan dulu — supaya kamu sempat menetapkan harga
                // jual sebelum TLD-nya muncul di pencarian domain.
                'is_active' => false,
            ]);

            $created++;
        }

        return back()->with('success',
            "Sinkronisasi selesai: {$created} TLD baru ditambahkan, {$skipped} sudah ada. " .
            ($created > 0 ? 'TLD baru masih NONAKTIF — tetapkan harga jualnya di TLD Pricing, lalu aktifkan.' : '')
        );
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'provider'     => ['required', 'in:namecheap,resellbiz,liquid'],
            // API URL opsional: kalau kosong, service memakai default
            // (Namecheap & Liqu.id sudah punya URL bawaan sandbox/produksi).
            'api_url'      => ['nullable', 'url', 'max:255'],
            'api_username' => ['required', 'string', 'max:100'],
            'api_key'      => [$updating ? 'nullable' : 'required', 'string'],
            'username'     => ['nullable', 'string', 'max:100'],
            'client_ip'    => ['nullable', 'ip'],
            'sandbox'      => ['nullable', 'boolean'],
            'is_active'    => ['nullable', 'boolean'],
            'is_default'   => ['nullable', 'boolean'],
        ]);
    }
}
