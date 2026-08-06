<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Registrar;
use App\Models\Tld;
use App\Services\Cart\CartService;
use App\Services\Domain\DomainRegistrarFactory;
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
    public function search(Request $request): View
    {
        $results = null;
        $query = trim((string) $request->input('domain'));

        $tldPrices = Tld::active()->orderBy('extension')->get()->keyBy('extension');

        // Ekstensi yang dicentang pengunjung. Belum pernah submit → pakai
        // daftar default; sudah pernah submit dengan centang kosong →
        // hormati pilihan itu (jangan diam-diam kembali ke default).
        $selected = $request->has('extensions')
            ? array_values(array_intersect((array) $request->input('extensions', []), $tldPrices->keys()->all()))
            : array_values(array_intersect(self::DEFAULT_EXTENSIONS, $tldPrices->keys()->all()));

        if ($query) {
            $registrar = Registrar::where('is_default', true)->where('is_active', true)->first()
                ?? Registrar::where('is_active', true)->first();

            if (! $registrar) {
                $results = ['success' => false, 'message' => 'Pencarian domain sedang tidak tersedia. Silakan coba lagi nanti.', 'results' => []];
            } elseif ($tldPrices->isEmpty()) {
                $results = ['success' => false, 'message' => 'Belum ada ekstensi domain yang dijual saat ini.', 'results' => []];
            } elseif (empty($selected)) {
                $results = ['success' => false, 'message' => 'Pilih minimal satu ekstensi untuk dicek.', 'results' => []];
            } else {
                $selected = array_slice($selected, 0, self::MAX_EXTENSIONS);

                $base = preg_replace('/\.[a-z.]+$/i', '', $query);
                $candidates = array_map(fn ($ext) => $base . $ext, $selected);

                $results = DomainRegistrarFactory::make($registrar)->checkAvailability($candidates);
            }
        }

        return view('public.catalog.domain-search', compact('results', 'query', 'tldPrices', 'selected'));
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
