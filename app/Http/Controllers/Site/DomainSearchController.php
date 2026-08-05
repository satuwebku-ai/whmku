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
     * Halaman cek domain publik — versi pengunjung dari "Cek Domain" admin,
     * tanpa perlu login. Memakai registrar default yang sama.
     */
    public function search(Request $request): View
    {
        $results = null;
        $query = trim((string) $request->input('domain'));

        $tldPrices = Tld::active()->orderBy('extension')->get()->keyBy('extension');

        if ($query) {
            $registrar = Registrar::where('is_default', true)->where('is_active', true)->first()
                ?? Registrar::where('is_active', true)->first();

            if (! $registrar) {
                $results = ['success' => false, 'message' => 'Pencarian domain sedang tidak tersedia. Silakan coba lagi nanti.', 'results' => []];
            } elseif ($tldPrices->isEmpty()) {
                $results = ['success' => false, 'message' => 'Belum ada ekstensi domain yang dijual saat ini.', 'results' => []];
            } else {
                $base = preg_replace('/\.[a-z.]+$/i', '', $query);
                $candidates = $tldPrices->keys()->map(fn ($ext) => $base . $ext)->values()->all();

                $results = DomainRegistrarFactory::make($registrar)->checkAvailability($candidates);
            }
        }

        return view('public.catalog.domain-search', compact('results', 'query', 'tldPrices'));
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
