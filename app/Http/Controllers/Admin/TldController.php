<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registrar;
use App\Models\Tld;
use App\Services\Domain\DomainRegistrarFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TldController extends Controller
{
    /**
     * Simpan harga add-on domain (ID Protection, dst). Bukan diambil dari
     * Liqu.id — lihat catatan di view TLD Pricing untuk alasannya.
     */
    public function updateAddonPricing(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'whois_privacy_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        \App\Models\Setting::put('whois_privacy_price', $data['whois_privacy_price'] ?? 0, 'domain');

        return back()->with('success', 'Harga add-on domain berhasil disimpan.');
    }

    public function index(Request $request): View
    {
        $tlds = Tld::with('registrar')
            ->when($request->search, fn ($q) => $q->where('extension', 'like', "%{$request->search}%"))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->web === 'shown', fn ($q) => $q->where('show_in_search', true))
            ->when($request->web === 'hidden', fn ($q) => $q->where('show_in_search', false))
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
            'shown'    => Tld::where('show_in_search', true)->count(),
            'hidden'   => Tld::where('show_in_search', false)->count(),
        ];

        $registrars = Registrar::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();

        return view('admin.tlds.index-bootstrap', compact('tlds', 'counts', 'registrars'));
    }

    public function indexBootstrap(Request $request): View
    {
        $tlds = Tld::with('registrar')
            ->when($request->search, fn ($q) => $q->where('extension', 'like', "%{$request->search}%"))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->web === 'shown', fn ($q) => $q->where('show_in_search', true))
            ->when($request->web === 'hidden', fn ($q) => $q->where('show_in_search', false))
            ->orderByDesc('is_active')
            ->orderBy('extension')
            ->paginate(min((int) $request->input('per_page', 25), 200))
            ->withQueryString();

        $counts = [
            'all'      => Tld::count(),
            'active'   => Tld::where('is_active', true)->count(),
            'inactive' => Tld::where('is_active', false)->count(),
            'no_cost'  => Tld::where('cost_register', '<=', 0)->count(),
            'shown'    => Tld::where('show_in_search', true)->count(),
            'hidden'   => Tld::where('show_in_search', false)->count(),
        ];

        $registrars = Registrar::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();

        return view('admin.tlds.index-bootstrap', compact('tlds', 'counts', 'registrars'));
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
     * Terapkan margin ke banyak TLD sekaligus — meniru panel reseller
     * (Set prices using Profit Margin).
     *
     * Margin bisa diatur terpisah untuk Register / Renew / Transfer,
     * dinyatakan dalam persen atau rupiah tetap, lalu dibulatkan sesuai
     * selera. Perhitungan SELALU dari harga modal, bukan dari harga jual
     * sebelumnya, jadi aman dijalankan berulang kali.
     */
    public function bulkMarkup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'profit_type'      => ['required', 'in:percent,fixed'],
            'margin_register'  => ['required', 'numeric', 'min:0'],
            'margin_renew'     => ['nullable', 'numeric', 'min:0'],
            'margin_transfer'  => ['nullable', 'numeric', 'min:0'],

            'round_mode'       => ['required', 'in:none,multiple,ending'],
            'round_step'       => ['nullable', 'integer', 'min:1'],
            'round_tail'       => ['nullable', 'integer', 'min:0'],

            'scope'            => ['required', 'in:all,selected,filtered'],
            'selected_ids'     => ['nullable', 'string'],
            'search'           => ['nullable', 'string'],

            'only_empty'       => ['nullable', 'boolean'],
            'activate'         => ['nullable', 'boolean'],
        ], [
            'margin_register.required' => 'Margin untuk Register wajib diisi.',
        ]);

        $type = $data['profit_type'];

        // Renew & Transfer mengikuti Register kalau dikosongkan.
        $margins = [
            'register' => (float) $data['margin_register'],
            'renew'    => $data['margin_renew'] !== null && $data['margin_renew'] !== ''
                ? (float) $data['margin_renew'] : (float) $data['margin_register'],
            'transfer' => $data['margin_transfer'] !== null && $data['margin_transfer'] !== ''
                ? (float) $data['margin_transfer'] : (float) $data['margin_register'],
        ];

        $query = Tld::query();

        if ($data['scope'] === 'selected') {
            $ids = array_filter(array_map('intval', explode(',', (string) ($data['selected_ids'] ?? ''))));

            if (empty($ids)) {
                return back()->with('error', 'Belum ada TLD yang dicentang di tabel.');
            }

            $query->whereIn('id', $ids);
        } elseif ($data['scope'] === 'filtered' && ! empty($data['search'])) {
            $query->where('extension', 'like', '%' . $data['search'] . '%');
        }

        if ($request->boolean('only_empty')) {
            $query->where(fn ($q) => $q->whereNull('register_price')->orWhere('register_price', '<=', 0));
        }

        $activate = $request->boolean('activate');
        $updated = 0;
        $skipped = 0;

        foreach ($query->get() as $tld) {
            if ((float) $tld->cost_register <= 0) {
                $skipped++;
                continue;
            }

            $costs = [
                'register' => (float) $tld->cost_register,
                'renew'    => (float) $tld->cost_renew ?: (float) $tld->cost_register,
                'transfer' => (float) $tld->cost_transfer ?: (float) $tld->cost_register,
            ];

            $prices = [];

            foreach ($costs as $field => $cost) {
                $price = $type === 'percent'
                    ? $cost * (1 + $margins[$field] / 100)
                    : $cost + $margins[$field];

                $prices[$field] = $this->roundPrice(
                    $price,
                    $data['round_mode'],
                    (int) ($data['round_step'] ?? 1000),
                    (int) ($data['round_tail'] ?? 0)
                );
            }

            $tld->update([
                'register_price' => $prices['register'],
                'renew_price'    => $prices['renew'],
                'transfer_price' => $prices['transfer'],
                'is_active'      => $activate && $prices['register'] > 0 ? true : $tld->is_active,
            ]);

            $updated++;
        }

        if ($updated === 0 && $skipped > 0) {
            return back()->with('error',
                "Tidak ada harga yang berubah. Semua {$skipped} TLD belum punya harga modal, " .
                'jadi margin tidak bisa dihitung. Isi kolom Modal dulu, atau pakai Impor Harga.'
            );
        }

        if ($updated === 0) {
            return back()->with('error', 'Tidak ada TLD yang cocok dengan kriteria yang dipilih.');
        }

        $label = $type === 'percent'
            ? "Margin {$margins['register']}%"
            : 'Margin Rp ' . number_format($margins['register'], 0, ',', '.');

        $msg = "{$label} diterapkan ke {$updated} TLD.";
        $msg .= $skipped > 0 ? " {$skipped} TLD dilewati karena belum punya harga modal." : '';

        return back()->with('success', $msg);
    }

    /**
     * Bulatkan harga sesuai mode yang dipilih.
     *
     * - none     : biarkan apa adanya
     * - multiple : bulatkan ke atas ke kelipatan tertentu (mis. 1.000)
     * - ending   : paksa digit akhir tertentu (mis. selalu berakhir 9.000)
     */
    private function roundPrice(float $price, string $mode, int $step, int $tail): float
    {
        if ($mode === 'multiple' && $step > 0) {
            return ceil($price / $step) * $step;
        }

        if ($mode === 'ending' && $step > 0) {
            $base = floor($price / $step) * $step + $tail;

            // Kalau hasilnya jadi lebih murah dari harga aslinya, naikkan
            // satu kelipatan supaya margin tidak berkurang.
            if ($base < $price) {
                $base += $step;
            }

            return $base;
        }

        return round($price, 2);
    }

    /**
     * Tarik harga modal langsung dari registrar, lalu tampilkan sebagai
     * tabel pratinjau. Tidak ada copy-paste dan belum ada yang disimpan.
     */
    public function syncPreview(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'registrar_id' => ['nullable', 'exists:registrars,id'],
            'markup'       => ['required', 'numeric', 'min:0', 'max:1000'],
            'round_to'     => ['nullable', 'integer', 'min:0'],
            'only_sellable' => ['nullable', 'boolean'],
        ]);

        $registrar = ! empty($data['registrar_id'])
            ? Registrar::find($data['registrar_id'])
            : Registrar::where('is_active', true)->orderByDesc('is_default')->first();

        if (! $registrar) {
            return back()->with('error', 'Belum ada registrar aktif. Tambahkan dulu di tab Registrar.');
        }

        $service = DomainRegistrarFactory::make($registrar);

        if (! method_exists($service, 'listPrices')) {
            return back()->with('error', "Provider {$registrar->provider} belum mendukung pengambilan harga otomatis.");
        }

        $result = $service->listPrices();

        if (! $result['success']) {
            return back()->with('error', 'Gagal mengambil harga: ' . $result['message']);
        }

        $markup  = (float) $data['markup'];
        $roundTo = (int) ($data['round_to'] ?? 1000);
        $onlySellable = $request->boolean('only_sellable');

        $existing = Tld::whereIn('extension', array_keys($result['prices']))->get()->keyBy('extension');

        $rows = [];

        foreach ($result['prices'] as $ext => $price) {
            // Lewati TLD yang belum diaktifkan untuk dijual di panel registrar.
            if ($onlySellable && isset($price['sellable']) && ! $price['sellable']) {
                continue;
            }

            $cost = (float) $price['register'];

            if ($cost <= 0) {
                continue;
            }

            $selling = $cost * (1 + $markup / 100);

            if ($roundTo > 0) {
                $selling = ceil($selling / $roundTo) * $roundTo;
            }

            $tld = $existing->get($ext);

            $rows[] = [
                'extension' => $ext,
                'cost'      => $cost,
                'selling'   => $selling,
                'exists'    => (bool) $tld,
                'tld_id'    => $tld?->id,
                // TLD yang sudah aktif tetap dicentang; yang baru mengikuti
                // status "Sell" di panel registrar.
                'active'    => (bool) ($tld?->is_active ?: ($price['sellable'] ?? false)),
                'old_cost'  => (float) ($tld?->cost_register ?? 0),
                'old_price' => (float) ($tld?->register_price ?? 0),
            ];
        }

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada harga yang bisa ditampilkan dari registrar ini.');
        }

        usort($rows, fn ($a, $b) => strcmp($a['extension'], $b['extension']));

        return view('admin.tlds.import-preview', [
            'rows'      => $rows,
            'markup'    => $markup,
            'roundTo'   => $roundTo,
            'source'    => $registrar->name,
        ]);
    }

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
            'source'  => null,
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
            'rows.*.search_group'     => ['nullable', 'string', 'max:50'],
        ]);

        $activeIds = array_map('intval', (array) $request->input('active', []));
        $searchIds = array_map('intval', (array) $request->input('in_search', []));
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
                // TLD tanpa harga jual tidak boleh tampil di halaman publik,
                // apa pun centangnya — kalau tidak, pengunjung melihat
                // domain seharga Rp 0.
                'show_in_search' => in_array((int) $id, $searchIds, true) && $register > 0,
                'search_group'   => $row['search_group'] ?? $tld->search_group,
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
            $msg = 'Tidak ada nilai yang berubah, jadi tidak ada yang perlu disimpan.';

            return $request->wantsJson()
                ? response()->json(['ok' => true, 'changed' => 0, 'message' => $msg])
                : back()->with('info', $msg);
        }

        $msg = "{$changed} TLD berhasil diperbarui.";

        if ($blocked) {
            $count = count($blocked);
            $sample = implode(', ', array_slice($blocked, 0, 5));
            $msg .= " {$count} TLD tidak jadi diaktifkan karena harga register-nya masih 0 ({$sample}" . ($count > 5 ? ', …' : '') . ').';
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'changed' => $changed, 'message' => $msg]);
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

        return view('admin.tlds.form-bootstrap', ['tld' => new Tld(), 'registrars' => $registrars]);
    }

    public function createBootstrap(): View
    {
        $registrars = Registrar::where('is_active', true)->orderBy('name')->get();

        return view('admin.tlds.form-bootstrap', ['tld' => new Tld(), 'registrars' => $registrars]);
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

        return view('admin.tlds.form-bootstrap', ['tld' => $tld, 'registrars' => $registrars]);
    }

    public function editBootstrap(Tld $tld): View
    {
        $registrars = Registrar::where('is_active', true)->orderBy('name')->get();

        return view('admin.tlds.form-bootstrap', ['tld' => $tld, 'registrars' => $registrars]);
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
        $data = $request->validate([
            'extension'      => ['required', 'string', 'max:30', 'unique:tlds,extension,' . $request->route('tld')?->id],
            'registrar_id'   => ['nullable', 'exists:registrars,id'],
            'register_price' => ['required', 'numeric', 'min:0'],
            'renew_price'    => ['required', 'numeric', 'min:0'],
            'transfer_price' => ['required', 'numeric', 'min:0'],
            'min_years'      => ['required', 'integer', 'min:1', 'max:10'],
            'max_years'      => ['required', 'integer', 'min:1', 'max:10'],
            'is_active'      => ['nullable', 'boolean'],
            'year_prices'    => ['nullable', 'array'],
            'year_prices.*'  => ['nullable', 'numeric', 'min:0'],
            'year_renew_prices'   => ['nullable', 'array'],
            'year_renew_prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Buang durasi yang dikosongkan supaya tidak tersimpan sebagai 0 —
        // nilai kosong berarti "pakai perhitungan otomatis".
        foreach (['year_prices', 'year_renew_prices'] as $field) {
            $data[$field] = collect($data[$field] ?? [])
                ->filter(fn ($v) => $v !== null && $v !== '' && (float) $v > 0)
                ->map(fn ($v) => (float) $v)
                ->all() ?: null;
        }

        return $data;
    }
}
