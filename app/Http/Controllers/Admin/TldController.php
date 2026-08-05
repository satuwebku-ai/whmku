<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registrar;
use App\Models\Tld;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TldController extends Controller
{
    public function index(Request $request): View
    {
        $tlds = Tld::with('registrar')
            ->when($request->search, fn ($q) => $q->where('extension', 'like', "%{$request->search}%"))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('extension')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'all'      => Tld::count(),
            'active'   => Tld::where('is_active', true)->count(),
            'inactive' => Tld::where('is_active', false)->count(),
            // Dipakai untuk memperingatkan bahwa markup tidak akan berpengaruh
            // pada TLD yang harga modalnya belum terisi.
            'no_cost'  => Tld::where('cost_register', '<=', 0)->count(),
        ];

        return view('admin.tlds.index', compact('tlds', 'counts'));
    }

    /**
     * Aktif/nonaktifkan satu TLD tanpa membuka form edit.
     */
    public function status(Request $request): RedirectResponse
    {
        $tld = Tld::findOrFail($request->input('tld_id'));

        // Mengaktifkan TLD tanpa harga jual hampir pasti tidak disengaja.
        if (! $tld->is_active && (float) $tld->register_price <= 0) {
            return back()->with('error', "TLD {$tld->extension} belum punya harga jual. Isi harganya dulu sebelum diaktifkan.");
        }

        $tld->update(['is_active' => ! $tld->is_active]);

        return back()->with('success', "TLD {$tld->extension} berhasil " . ($tld->is_active ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    /**
     * Terapkan harga jual ke banyak TLD sekaligus.
     *
     * Dua mode:
     *  - markup : harga jual = harga modal + persentase (butuh harga modal)
     *  - fixed  : harga jual diisi nilai tetap (dipakai kalau harga modal
     *             belum tersedia dari registrar)
     *
     * Harga modal TIDAK pernah ditimpa di sini, jadi markup aman dijalankan
     * berulang kali — hasilnya selalu dihitung dari modal, bukan dari harga
     * jual sebelumnya.
     */
    public function bulkMarkup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode'           => ['required', 'in:markup,fixed'],
            'markup_percent' => ['required_if:mode,markup', 'nullable', 'numeric', 'min:0', 'max:1000'],
            'fixed_register' => ['required_if:mode,fixed', 'nullable', 'numeric', 'min:0'],
            'fixed_renew'    => ['nullable', 'numeric', 'min:0'],
            'fixed_transfer' => ['nullable', 'numeric', 'min:0'],
            'round_to'       => ['nullable', 'integer', 'min:0'],
            'only_empty'     => ['nullable', 'boolean'],
            'activate'       => ['nullable', 'boolean'],
            'scope'          => ['nullable', 'in:all,filtered'],
            'search'         => ['nullable', 'string'],
        ], [
            'markup_percent.required_if' => 'Persentase markup wajib diisi.',
            'fixed_register.required_if' => 'Harga register wajib diisi untuk mode harga tetap.',
        ]);

        $roundTo   = (int) ($data['round_to'] ?? 1000);
        $activate  = $request->boolean('activate');
        $onlyEmpty = $request->boolean('only_empty');

        // Batasi ke hasil pencarian bila diminta, supaya bisa mengatur
        // sekelompok TLD saja (mis. hanya ".id").
        $query = Tld::query();

        if (($data['scope'] ?? 'all') === 'filtered' && ! empty($data['search'])) {
            $query->where('extension', 'like', '%' . $data['search'] . '%');
        }

        if ($onlyEmpty) {
            $query->where(fn ($q) => $q->whereNull('register_price')->orWhere('register_price', '<=', 0));
        }

        $updated = 0;
        $skipped = 0;

        foreach ($query->get() as $tld) {
            if ($data['mode'] === 'markup') {
                $base = (float) $tld->cost_register;

                // Tanpa harga modal, markup tidak punya dasar hitung.
                if ($base <= 0) {
                    $skipped++;
                    continue;
                }

                $percent  = (float) $data['markup_percent'];
                $register = $base * (1 + $percent / 100);
                $renew    = ((float) $tld->cost_renew ?: $base) * (1 + $percent / 100);
                $transfer = ((float) $tld->cost_transfer ?: $base) * (1 + $percent / 100);
            } else {
                $register = (float) $data['fixed_register'];
                $renew    = isset($data['fixed_renew']) && $data['fixed_renew'] !== null
                    ? (float) $data['fixed_renew'] : $register;
                $transfer = isset($data['fixed_transfer']) && $data['fixed_transfer'] !== null
                    ? (float) $data['fixed_transfer'] : $register;
            }

            if ($roundTo > 0) {
                $register = ceil($register / $roundTo) * $roundTo;
                $renew    = ceil($renew / $roundTo) * $roundTo;
                $transfer = ceil($transfer / $roundTo) * $roundTo;
            }

            $tld->update([
                'register_price' => $register,
                'renew_price'    => $renew,
                'transfer_price' => $transfer,
                'is_active'      => $activate && $register > 0 ? true : $tld->is_active,
            ]);

            $updated++;
        }

        if ($updated === 0 && $skipped > 0) {
            return back()->with('error',
                "Tidak ada harga yang berubah. Semua {$skipped} TLD belum punya harga modal, " .
                "jadi markup tidak bisa dihitung. Jalankan \"Sinkronkan TLD\" di tab Registrar untuk " .
                "mengambil harga modal, atau pakai mode \"Harga Tetap\" untuk mengisi harga jual langsung."
            );
        }

        if ($updated === 0) {
            return back()->with('error', 'Tidak ada TLD yang cocok dengan kriteria yang dipilih.');
        }

        $label = $data['mode'] === 'markup'
            ? "Markup {$data['markup_percent']}%"
            : 'Harga tetap';

        $msg = "{$label} diterapkan ke {$updated} TLD.";
        $msg .= $skipped > 0 ? " {$skipped} TLD dilewati karena belum punya harga modal." : '';

        return back()->with('success', $msg);
    }

    /**
     * Impor harga modal dari teks yang ditempel.
     *
     * Dipakai karena endpoint /tlds Liqu.id tidak menyertakan harga sama
     * sekali, sementara harganya tersedia di halaman pricing reseller.
     * Tinggal blok-copy tabelnya dan tempel di sini.
     *
     * Format yang diterima per baris (pemisah: tab, koma, titik koma,
     * atau spasi ganda):
     *   .com    170.33
     *   .id, 365390
     *   .co.id; 420.10; 840.19       ← kolom ke-2 dipakai, sisanya diabaikan
     */
    public function importPrices(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'price_text'  => ['required', 'string'],
            'multiplier'  => ['required', 'numeric', 'min:1'],
            'create_missing' => ['nullable', 'boolean'],
        ]);

        $multiplier = (float) $data['multiplier'];
        $createMissing = $request->boolean('create_missing');

        $updated = 0;
        $created = 0;
        $unknown = [];

        foreach (preg_split('/\R/', $data['price_text']) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // Pecah baris jadi kolom.
            $parts = preg_split('/\t|,|;|\s{2,}|\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);

            if (count($parts) < 2) {
                continue;
            }

            $ext = '.' . ltrim(strtolower(trim($parts[0])), '.');

            // Cari angka pertama setelah kolom ekstensi. Teks seperti
            // "IDR" atau "(in 1000's)" dilewati otomatis.
            $price = null;

            foreach (array_slice($parts, 1) as $part) {
                $clean = str_replace(',', '', trim($part));

                if (is_numeric($clean)) {
                    $price = (float) $clean;
                    break;
                }
            }

            if ($price === null || $price <= 0) {
                continue;
            }

            $cost = round($price * $multiplier, 2);

            $tld = Tld::where('extension', $ext)->first();

            if (! $tld) {
                if (! $createMissing) {
                    $unknown[] = $ext;
                    continue;
                }

                $tld = new Tld([
                    'extension' => $ext,
                    'register_price' => 0,
                    'renew_price' => 0,
                    'transfer_price' => 0,
                    'min_years' => 1,
                    'max_years' => 10,
                    'is_active' => false,
                ]);
                $created++;
            } else {
                $updated++;
            }

            $tld->fill([
                'cost_register'  => $cost,
                'cost_renew'     => $cost,
                'cost_transfer'  => $cost,
                'cost_currency'  => 'IDR',
                'cost_synced_at' => now(),
            ])->save();
        }

        if ($updated === 0 && $created === 0) {
            return back()->with('error',
                'Tidak ada harga yang terbaca. Pastikan tiap baris berisi ekstensi lalu harganya, contoh: ".com    170.33"'
            );
        }

        $msg = "Harga modal diperbarui untuk {$updated} TLD";
        $msg .= $created > 0 ? ", {$created} TLD baru dibuat." : '.';

        if ($unknown) {
            $count = count($unknown);
            $sample = implode(', ', array_slice($unknown, 0, 5));
            $msg .= " {$count} ekstensi dilewati karena belum ada di daftar ({$sample}" . ($count > 5 ? ', …' : '') . ').';
        }

        $msg .= ' Selanjutnya pakai "Markup Massal" untuk menetapkan harga jual.';

        return back()->with('success', $msg);
    }

    public function create(): View
    {
        $registrars = Registrar::where('is_active', true)->orderBy('name')->get();

        return view('admin.tlds.form', ['tld' => new Tld(), 'registrars' => $registrars]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);

        Tld::create($data);

        return redirect()->route('admin.tlds.index')->with('success', 'TLD berhasil ditambahkan.');
    }

    public function edit(Tld $tld): View
    {
        $registrars = Registrar::where('is_active', true)->orderBy('name')->get();

        return view('admin.tlds.form', ['tld' => $tld, 'registrars' => $registrars]);
    }

    public function update(Request $request, Tld $tld): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        $tld->update($data);

        return redirect()->route('admin.tlds.index')->with('success', 'TLD berhasil diperbarui.');
    }

    public function destroy(Tld $tld): RedirectResponse
    {
        $tld->delete();

        return redirect()->route('admin.tlds.index')->with('success', 'TLD berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'extension'      => ['required', 'string', 'max:30', 'unique:tlds,extension,' . $request->route('tld')?->id],
            'registrar_id'   => ['nullable', 'exists:registrars,id'],
            'register_price' => ['required', 'numeric', 'min:0'],
            'renew_price'    => ['required', 'numeric', 'min:0'],
            'transfer_price' => ['required', 'numeric', 'min:0'],
            'min_years'      => ['required', 'integer', 'min:1', 'max:10'],
            'max_years'      => ['required', 'integer', 'min:1', 'max:10'],
            'is_active'      => ['nullable', 'boolean'],
        ]);
    }
}
