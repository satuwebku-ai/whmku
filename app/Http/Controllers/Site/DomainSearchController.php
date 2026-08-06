<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Tld;
use App\Services\Cart\CartService;
use App\Services\Domain\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainSearchController extends Controller
{
    /**
     * Jumlah maksimum ekstensi yang boleh dicek sekaligus.
     *
     * Sebelumnya SEMUA TLD aktif (bisa ratusan) dikirim otomatis di setiap
     * pencarian — itu yang membuat halaman ini sering timeout atau ditolak
     * registrar (device batch terlalu besar). Sekarang pengunjung memilih
     * sendiri ekstensi yang dicek lewat checkbox, dibatasi jumlahnya di sini
     * sebagai jaring pengaman kalau ada yang mencoba mengirim lebih banyak.
     */
    private const MAX_EXTENSIONS = 30;

    /**
     * Ekstensi yang dicentang otomatis saat halaman pertama dibuka —
     * yang paling umum dicari, supaya pengunjung tidak perlu mencentang
     * manual untuk kasus paling umum.
     */
    private const DEFAULT_EXTENSIONS = ['.com', '.net', '.id', '.co.id', '.my.id', '.web.id', '.biz', '.info'];

    /**
     * Halaman cek domain publik — versi pengunjung dari "Cek Domain" admin,
     * tanpa perlu login. Memakai registrar default yang sama.
     */
    public function search(Request $request, AvailabilityService $checker): View
    {
        $results = null;
        $query = trim((string) $request->input('domain'));

        // Hanya ekstensi yang sengaja ditampilkan admin — bukan seluruh TLD.
        $tldPrices = Tld::visibleInSearch()
            ->orderBy('search_order')
            ->orderBy('extension')
            ->get()
            ->keyBy('extension');

        // Dikelompokkan untuk sidebar kategori.
        $groups = $tldPrices->groupBy(fn ($tld) => $tld->search_group_label);

        $selected = $request->has('extensions')
            ? array_values(array_intersect((array) $request->input('extensions', []), $tldPrices->keys()->all()))
            : array_values(array_intersect(self::DEFAULT_EXTENSIONS, $tldPrices->keys()->all()));

        // Kalau tidak satu pun default tersedia, pakai beberapa yang termurah
        // supaya halaman tidak tampil tanpa satu pun centang.
        if (empty($selected) && ! $request->has('extensions')) {
            $selected = $tldPrices->sortBy('register_price')->take(6)->keys()->all();
        }

        if ($query) {
            if ($tldPrices->isEmpty()) {
                $results = ['success' => false, 'message' => 'Belum ada ekstensi domain yang dijual saat ini.', 'results' => [], 'unknown' => []];
            } elseif (empty($selected)) {
                $results = ['success' => false, 'message' => 'Pilih minimal satu ekstensi untuk dicek.', 'results' => [], 'unknown' => []];
            } else {
                $selected = array_slice($selected, 0, self::MAX_EXTENSIONS);

                // Ambil bagian nama saja: "saya.com" maupun "saya" sama-sama
                // menghasilkan "saya", lalu digabung dengan tiap ekstensi.
                $base = $this->normalizeName($query);

                if ($base === '') {
                    $results = ['success' => false, 'message' => 'Nama domain tidak valid. Gunakan huruf, angka, dan tanda hubung.', 'results' => [], 'unknown' => []];
                } else {
                    $candidates = array_map(fn ($ext) => $base . $ext, $selected);

                    // Pengecekan memakai RDAP publik, bukan API registrar —
                    // lihat AvailabilityService untuk alasannya.
                    $results = $checker->check($candidates);
                }
            }
        }

        return view('public.catalog.domain-search', compact('results', 'query', 'tldPrices', 'selected', 'groups'));
    }

    /**
     * Bersihkan input jadi nama domain yang sah (tanpa ekstensi).
     *
     * Pengunjung sering mengetik "saya.com", "www.saya", atau menyertakan
     * spasi — semuanya diringkas jadi "saya" supaya bisa digabung dengan
     * ekstensi pilihan. Karakter di luar huruf/angka/tanda hubung dibuang
     * karena akan ditolak registry.
     */
    private function normalizeName(string $query): string
    {
        $name = strtolower(trim($query));
        $name = preg_replace('/^https?:\/\//', '', $name);
        $name = preg_replace('/^www\./', '', $name);
        $name = explode('/', $name)[0];

        // Buang ekstensi kalau pengunjung mengetiknya sekalian.
        $name = explode('.', $name)[0];

        // Hanya huruf, angka, dan tanda hubung; tidak boleh diawali/diakhiri
        // tanda hubung.
        $name = preg_replace('/[^a-z0-9\-]/', '', $name);

        return trim($name, '-');
    }

    /**
     * Tambahkan domain yang tersedia ke keranjang.
     */
    public function addToCart(Request $request, CartService $cart): RedirectResponse
    {
        $data = $request->validate([
            'domain_name' => ['required', 'string', 'max:255'],
            'tld_id'      => ['required', 'exists:tlds,id'],
            'years'       => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $tld = Tld::findOrFail($data['tld_id']);

        $result = $cart->addDomain($data['domain_name'], $tld, (int) ($data['years'] ?? 1));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
