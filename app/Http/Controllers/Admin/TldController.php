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
            // Bisa diperbesar supaya lebih banyak baris diedit sekaligus.
            ->paginate(min((int) $request->input('per_page', 25), 200))
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
    /**
     * Langkah 1 impor: baca teks yang ditempel, lalu tampilkan sebagai
     * tabel pratinjau. BELUM ada yang disimpan di sini — supaya kamu bisa
     * memeriksa dan menyesuaikan tiap baris sebelum benar-benar diterapkan.
     */
    public function importPreview(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'price_text'   => ['required', 'string'],
            'multiplier'   => ['required', 'numeric', 'min:1'],
            // Panel "Manage Prices" Liqu.id menampilkan dua angka per baris:
            // angka ke-1 = harga jual ke customer, angka ke-2 = harga modal
            // reseller. Yang dipakai untuk markup adalah yang ke-2.
            'price_column' => ['required', 'integer', 'min:1', 'max:3'],
            'markup'       => ['required', 'numeric', 'min:0', 'max:1000'],
            'round_to'     => ['nullable', 'integer', 'min:0'],
        ]);

        $parsed = $this->parsePriceText(
            $data['price_text'],
            (int) $data['price_column'],
            (float) $data['multiplier']
        );

        if (empty($parsed)) {
            return back()->with('error',
                'Tidak ada harga yang terbaca. Pastikan tiap baris berisi ekstensi lalu angkanya, contoh: ".COM Domain Names  170.33 163.44  4.22 %"'
            );
        }

        $markup  = (float) $data['markup'];
        $roundTo = (int) ($data['round_to'] ?? 1000);

        $existing = Tld::whereIn('extension', array_keys($parsed))->get()->keyBy('extension');

        $rows = [];

        foreach ($parsed as $ext => $cost) {
            $tld = $existing->get($ext);

            $selling = $cost * (1 + $markup / 100);

            if ($roundTo > 0) {
                $selling = ceil($selling / $roundTo) * $roundTo;
            }

            $rows[] = [
                'extension' => $ext,
                'cost'      => $cost,
                'selling'   => $selling,
                'exists'    => (bool) $tld,
                'tld_id'    => $tld?->id,
                // TLD yang sudah aktif tetap dicentang; yang baru dibiarkan
                // mati supaya tidak langsung tampil sebelum dicek.
                'active'    => (bool) ($tld?->is_active),
                'old_cost'  => (float) ($tld?->cost_register ?? 0),
                'old_price' => (float) ($tld?->register_price ?? 0),
            ];
        }

        return view('admin.tlds.import-preview', [
            'rows'    => $rows,
            'markup'  => $markup,
            'roundTo' => $roundTo,
        ]);
    }

    /**
     * Langkah 2 impor: simpan baris-baris yang dicentang dari pratinjau.
     */
    public function importApply(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rows'                  => ['required', 'array'],
            'rows.*.extension'      => ['required', 'string', 'max:30'],
            'rows.*.cost'           => ['nullable', 'numeric', 'min:0'],
            'rows.*.selling'        => ['nullable', 'numeric', 'min:0'],
            'include'               => ['nullable', 'array'],
        ]);

        $include = array_map('strval', (array) $request->input('include', []));
        $activate = array_map('strval', (array) $request->input('active', []));

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($data['rows'] as $key => $row) {
            // Baris yang tidak dicentang sengaja dilewati.
            if (! in_array((string) $key, $include, true)) {
                $skipped++;
                continue;
            }

            $ext = '.' . ltrim(strtolower(trim($row['extension'])), '.');
            $cost = round((float) ($row['cost'] ?? 0), 2);
            $selling = round((float) ($row['selling'] ?? 0), 2);
            $isActive = in_array((string) $key, $activate, true) && $selling > 0;

            $tld = Tld::where('extension', $ext)->first();

            $values = [
                'cost_register'  => $cost,
                'cost_renew'     => $cost,
                'cost_transfer'  => $cost,
                'cost_currency'  => 'IDR',
                'cost_synced_at' => $cost > 0 ? now() : null,
                'register_price' => $selling,
                'renew_price'    => $selling,
                'transfer_price' => $selling,
                'is_active'      => $isActive,
            ];

            if ($tld) {
                $tld->update($values);
                $updated++;
            } else {
                Tld::create(array_merge([
                    'extension' => $ext,
                    'min_years' => 1,
                    'max_years' => 10,
                ], $values));
                $created++;
            }
        }

        if ($created === 0 && $updated === 0) {
            return redirect()->route('admin.tlds.index')
                ->with('error', 'Tidak ada baris yang dicentang, jadi tidak ada yang disimpan.');
        }

        $msg = "Impor selesai — {$updated} TLD diperbarui";
        $msg .= $created > 0 ? ", {$created} TLD baru dibuat." : '.';
        $msg .= $skipped > 0 ? " {$skipped} baris dilewati." : '';

        return redirect()->route('admin.tlds.index')->with('success', $msg);
    }

    /**
     * Baca teks daftar harga jadi map [ekstensi => harga modal].
     *
     * Menerima pemisah tab, koma, titik koma, atau spasi. Teks seperti
     * "Domain Names", "IDR", dan "%" diabaikan karena bukan angka.
     */
    private function parsePriceText(string $text, int $column, float $multiplier): array
    {
        $result = [];

        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\t|,|;|\s{2,}|\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);

            if (count($parts) < 2) {
                continue;
            }

            $ext = '.' . ltrim(strtolower(trim($parts[0])), '.');

            $numbers = [];

            foreach (array_slice($parts, 1) as $part) {
                $clean = str_replace(',', '', trim($part));

                if (is_numeric($clean) && (float) $clean > 0) {
                    $numbers[] = (float) $clean;
                }
            }

            $price = $numbers[$column - 1] ?? ($numbers[0] ?? null);

            if ($price === null || $price <= 0) {
                continue;
            }

            $result[$ext] = round($price * $multiplier, 2);
        }

        return $result;
    }

    /**
     * Simpan perubahan harga yang diketik langsung di tabel.
     *
     * Jauh lebih praktis daripada membuka form edit satu per satu, dan
     * hanya baris yang benar-benar berubah yang disentuh database.
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rows'                    => ['required', 'array'],
            'rows.*.cost_register'    => ['nullable', 'numeric', 'min:0'],
            'rows.*.register_price'   => ['nullable', 'numeric', 'min:0'],
            'rows.*.renew_price'      => ['nullable', 'numeric', 'min:0'],
            'rows.*.transfer_price'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $activeIds = array_map('intval', (array) $request->input('active', []));
        $changed = 0;
        $blocked = [];

        $tlds = Tld::whereIn('id', array_keys($data['rows']))->get()->keyBy('id');

        foreach ($data['rows'] as $id => $row) {
            $tld = $tlds->get((int) $id);

            if (! $tld) {
                continue;
            }

            $register = $this->toNumber($row['register_price'] ?? null, $tld->register_price);
            $wantActive = in_array((int) $id, $activeIds, true);

            // Mengaktifkan TLD tanpa harga jual akan membuatnya tampil di
            // pencarian domain seharga Rp 0 — dicegah di sini.
            if ($wantActive && $register <= 0) {
                $blocked[] = $tld->extension;
                $wantActive = false;
            }

            $values = [
                'cost_register'  => $this->toNumber($row['cost_register'] ?? null, $tld->cost_register),
                'register_price' => $register,
                'renew_price'    => $this->toNumber($row['renew_price'] ?? null, $tld->renew_price),
                'transfer_price' => $this->toNumber($row['transfer_price'] ?? null, $tld->transfer_price),
                'is_active'      => $wantActive,
            ];

            // Kalau harga modal baru diisi manual, catat waktunya.
            if ($values['cost_register'] > 0 && (float) $tld->cost_register !== $values['cost_register']) {
                $values['cost_synced_at'] = now();
            }

            $tld->fill($values);

            if ($tld->isDirty()) {
                $tld->save();
                $changed++;
            }
        }

        if ($changed === 0) {
            return back()->with('error', 'Tidak ada perubahan yang disimpan.');
        }

        $msg = "{$changed} TLD berhasil diperbarui.";

        if ($blocked) {
            $count = count($blocked);
            $sample = implode(', ', array_slice($blocked, 0, 5));
            $msg .= " {$count} TLD tidak jadi diaktifkan karena harga register-nya masih 0 ({$sample}" . ($count > 5 ? ', …' : '') . ').';
        }

        return back()->with($blocked ? 'error' : 'success', $msg);
    }

    /**
     * Ubah input jadi angka; kosong berarti pakai nilai lama.
     */
    private function toNumber(mixed $value, mixed $fallback): float
    {
        if ($value === null || $value === '') {
            return (float) $fallback;
        }

        return round((float) $value, 2);
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
