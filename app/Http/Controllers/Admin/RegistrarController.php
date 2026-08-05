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

        // Endpoint daftar TLD tidak menyertakan harga, jadi harga modal
        // diambil terpisah. Kalau gagal, sinkronisasi tetap jalan — TLD
        // tetap masuk, harganya saja yang perlu diisi manual.
        $prices = [];
        $priceMessage = '';

        if (method_exists($service, 'listPrices')) {
            $priceResult = $service->listPrices();

            if ($priceResult['success']) {
                $prices = $priceResult['prices'];
            } else {
                $priceMessage = ' Harga modal TIDAK terambil: ' . $priceResult['message'];
            }
        } else {
            $priceMessage = ' Provider ini belum mendukung pengambilan harga otomatis.';
        }

        $created = 0;
        $skipped = 0;
        $priced  = 0;

        foreach ($result['tlds'] as $row) {
            $existing = Tld::where('extension', $row['extension'])->first();

            $price = $prices[$row['extension']] ?? null;

            $costRegister = $price['register'] ?? ($row['price'] !== null ? (float) $row['price'] : 0);
            $costRenew    = $price['renew'] ?? $costRegister;
            $costTransfer = $price['transfer'] ?? $costRegister;

            $costFields = [
                'cost_register'  => $costRegister ?: 0,
                'cost_renew'     => $costRenew ?: 0,
                'cost_transfer'  => $costTransfer ?: 0,
                'cost_currency'  => $price['currency'] ?? 'IDR',
                // Hanya dicap tersinkron kalau harganya benar-benar didapat.
                // Sebelumnya selalu diisi, sehingga TLD berharga 0 terlihat
                // seolah sudah tersinkron padahal harganya gagal diambil.
                'cost_synced_at' => $costRegister > 0 ? now() : null,
            ];

            if ($costRegister > 0) {
                $priced++;
            }

            if ($existing) {
                // Harga MODAL selalu diperbarui (itu data dari registrar),
                // tapi harga JUAL tidak disentuh supaya markup yang sudah
                // kamu atur tidak hilang.
                $update = $costFields;

                if (! $existing->registrar_id) {
                    $update['registrar_id'] = $registrar->id;
                }

                $existing->update($update);
                $skipped++;
                continue;
            }

            Tld::create(array_merge([
                'extension' => $row['extension'],
                'registrar_id' => $registrar->id,
                // Harga JUAL sengaja dibiarkan 0 — diisi lewat Markup Massal
                // atau manual, supaya tidak ada TLD terjual seharga modal.
                'register_price' => 0,
                'renew_price' => 0,
                'transfer_price' => 0,
                'min_years' => $row['min_years'] ?? 1,
                'max_years' => $row['max_years'] ?? 10,
                // Dinonaktifkan dulu — supaya kamu sempat menetapkan harga
                // jual sebelum TLD-nya muncul di pencarian domain.
                'is_active' => false,
            ], $costFields));

            $created++;
        }

        if ($created === 0 && $skipped === 0) {
            return back()->with('error', 'Tidak ada TLD yang bisa diimpor dari registrar ini.');
        }

        $message = "Sinkronisasi selesai — {$created} TLD baru";
        $message .= $skipped > 0 ? ", {$skipped} diperbarui." : '.';
        $message .= " Harga modal terisi untuk {$priced} TLD.";
        $message .= $priceMessage;

        if ($priced > 0) {
            $message .= ' Selanjutnya pakai "Markup Massal" untuk menetapkan harga jual, lalu aktifkan TLD yang ingin dijual.';
        }

        return back()->with($priceMessage ? 'error' : 'success', $message);
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
