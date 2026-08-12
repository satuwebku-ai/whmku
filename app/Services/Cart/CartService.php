<?php

namespace App\Services\Cart;

use App\Models\Product;
use App\Models\Tld;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Keranjang belanja berbasis session — sengaja TIDAK memakai tabel
 * database, supaya pengunjung yang belum login/daftar tetap bisa
 * menambah item. Isi keranjang baru "menjadi nyata" sebagai Order +
 * Invoice saat checkout (Fase 7c), setelah klien login/registrasi.
 *
 * Struktur satu item:
 *   [
 *     'key'           => string acak, id baris di keranjang
 *     'type'          => 'product' | 'domain'
 *     'name'          => nama yang tampil
 *     'billing_cycle' => untuk type=product
 *     'price'         => harga per siklus/tahun (snapshot saat ditambahkan)
 *     'years'         => untuk type=domain
 *     'product_id' / 'tld_id' => referensi ke data asli
 *     'domain_mode'   => 'register' | 'existing' | null — untuk produk hosting
 *     'domain_name'   => nama domain yang menyertai produk, kalau ada
 *   ]
 */
class CartService
{
    private const SESSION_KEY = 'cart';

    /**
     * @return array<int, array>
     */
    public function items(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Sinkronkan ulang harga item domain di keranjang dengan harga
     * TERKINI (TLD & add-on ID Protection) — dipanggil tiap kali halaman
     * Keranjang dibuka.
     *
     * Tanpa ini, item yang sudah lebih dulu ada di keranjang akan tetap
     * memakai harga lama selamanya (harga "dibekukan" saat ditambahkan,
     * supaya tidak berubah tiba-tiba di tengah proses checkout) — kalau
     * admin baru saja mengubah harga TLD atau harga add-on setelah klien
     * menambah domain, klien akan bingung kenapa perubahan itu tidak
     * pernah terlihat. Disegarkan di sini setiap kunjungan ke halaman
     * Keranjang supaya harga yang ditampilkan selalu yang terbaru, tanpa
     * klien perlu menghapus dan menambah ulang manual.
     */
    public function refreshPricing(): void
    {
        $items = $this->items();
        $changed = false;

        foreach ($items as &$item) {
            if (($item['type'] ?? null) !== 'domain' || empty($item['tld_id'])) {
                continue;
            }

            $tld = \App\Models\Tld::find($item['tld_id']);

            if (! $tld) {
                continue;
            }

            $newBase = $tld->priceForYears((int) ($item['years'] ?? 1));
            $newAddon = (float) \App\Models\Setting::get('whois_privacy_price', 0);

            if (($item['base_price'] ?? null) != $newBase || ($item['whois_privacy_price'] ?? null) != $newAddon) {
                $item['base_price'] = $newBase;
                $item['whois_privacy_price'] = $newAddon;
                $item['price'] = ($item['whois_privacy'] ?? false) ? $newBase + $newAddon : $newBase;
                $changed = true;
            }
        }
        unset($item);

        if ($changed) {
            Session::put(self::SESSION_KEY, $items);
        }
    }

    public function count(): int
    {
        return count($this->items());
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function subtotal(): float
    {
        return array_sum(array_column($this->items(), 'price'));
    }

    /**
     * Tambah produk hosting/layanan ke keranjang.
     *
     * @return array{success: bool, message: string}
     */
    public function addProduct(Product $product, string $cycle, ?string $domainMode = null, ?string $domainName = null, ?string $transferAuthCode = null): array
    {
        if (! $product->is_active) {
            return ['success' => false, 'message' => 'Produk ini sedang tidak tersedia.'];
        }

        if (! $product->isInStock()) {
            return ['success' => false, 'message' => 'Stok produk ini sedang habis.'];
        }

        $price = $product->priceForCycle($cycle);

        if ($price === null) {
            return ['success' => false, 'message' => 'Siklus tagihan yang dipilih tidak tersedia untuk produk ini.'];
        }

        if ($product->requiresDomain() && blank($domainName)) {
            return ['success' => false, 'message' => 'Produk ini wajib disertai nama domain.'];
        }

        if ($domainMode === 'transfer' && blank($transferAuthCode)) {
            return ['success' => false, 'message' => 'Kode EPP/Auth diperlukan untuk transfer domain — minta dari registrar domain Anda saat ini.'];
        }

        $this->push([
            'key'           => (string) Str::uuid(),
            'type'          => 'product',
            'product_id'    => $product->id,
            'name'          => $product->name,
            'billing_cycle' => $cycle,
            'price'         => $price,
            'setup_fee'     => (float) $product->setup_fee,
            'domain_mode'   => $product->allowsDomain() ? $domainMode : null,
            'domain_name'   => $product->allowsDomain() ? $domainName : null,
            'transfer_auth_code' => $product->allowsDomain() && $domainMode === 'transfer' ? $transferAuthCode : null,
        ]);

        return ['success' => true, 'message' => "{$product->name} ditambahkan ke keranjang."];
    }

    /**
     * Tambah domain baru (hasil dari halaman Cek Domain) ke keranjang.
     *
     * @return array{success: bool, message: string}
     */
    public function addDomain(string $domainName, Tld $tld, int $years = 1): array
    {
        if (! $tld->is_active) {
            return ['success' => false, 'message' => 'Ekstensi domain ini sedang tidak dijual.'];
        }

        $years = max($tld->min_years, min($years, $tld->max_years));

        $domainName = strtolower(trim($domainName));

        // Domain yang sama tidak boleh masuk dua kali — kalau lolos, klien
        // akan ditagih ganda untuk satu domain yang hanya bisa didaftarkan
        // sekali.
        foreach ($this->items() as $item) {
            if (($item['type'] ?? null) === 'domain' && strtolower($item['domain_name'] ?? '') === $domainName) {
                return ['success' => false, 'message' => "{$domainName} sudah ada di keranjang Anda."];
            }
        }

        // Domain yang sudah terdaftar di sistem tidak bisa dipesan lagi.
        if (\App\Models\Domain::where('domain_name', $domainName)
            ->whereIn('status', ['pending', 'active'])
            ->exists()) {
            return ['success' => false, 'message' => "{$domainName} sudah terdaftar dan tidak bisa dipesan lagi."];
        }

        // Harga ID Protection diambil sekali (snapshot) saat ditambahkan,
        // supaya kalau admin mengubah harganya nanti, item yang sudah ada
        // di keranjang klien lain tidak ikut berubah harganya diam-diam.
        $privacyPrice = (float) \App\Models\Setting::get('whois_privacy_price', 0);
        $basePrice = $tld->priceForYears($years);

        $this->push([
            'key'         => (string) Str::uuid(),
            'type'        => 'domain',
            'tld_id'      => $tld->id,
            'domain_name' => $domainName,
            'years'       => $years,
            'base_price'  => $basePrice,
            'whois_privacy_price' => $privacyPrice,
            // ID Protection dinyalakan default → harga awal sudah termasuk add-on-nya.
            'price'       => $basePrice + $privacyPrice,
            'whois_privacy' => true,
        ]);

        return ['success' => true, 'message' => "{$domainName} ditambahkan ke keranjang."];
    }

    /**
     * Nyalakan/matikan ID Protection untuk satu item domain di keranjang,
     * dan sesuaikan harganya (tambah/kurangi harga add-on yang sudah
     * di-snapshot saat item ditambahkan).
     */
    public function toggleWhoisPrivacy(string $key): void
    {
        $items = $this->items();

        foreach ($items as &$item) {
            if ($item['key'] === $key && ($item['type'] ?? null) === 'domain') {
                $item['whois_privacy'] = ! ($item['whois_privacy'] ?? false);

                $base = $item['base_price'] ?? $item['price'];
                $addon = $item['whois_privacy_price'] ?? 0;
                $item['price'] = $item['whois_privacy'] ? $base + $addon : $base;
            }
        }
        unset($item);

        Session::put(self::SESSION_KEY, $items);
    }

    public function remove(string $key): void
    {
        $items = collect($this->items())->reject(fn ($item) => $item['key'] === $key)->values()->all();

        Session::put(self::SESSION_KEY, $items);
    }

    /**
     * Ubah lama tahun untuk item domain (harga ikut dihitung ulang).
     */
    public function updateDomainYears(string $key, int $years): void
    {
        $items = $this->items();

        foreach ($items as &$item) {
            if ($item['key'] === $key && $item['type'] === 'domain') {
                $tld = Tld::find($item['tld_id']);

                if ($tld) {
                    $years = max($tld->min_years, min($years, $tld->max_years));
                    $item['years'] = $years;
                    // Harus memakai perhitungan yang sama dengan addDomain,
                    // kalau tidak harga bisa berubah hanya karena pengguna
                    // mengubah durasi bolak-balik.
                    $item['base_price'] = $tld->priceForYears($years);
                    $addon = $item['whois_privacy_price'] ?? 0;
                    $item['price'] = ($item['whois_privacy'] ?? false) ? $item['base_price'] + $addon : $item['base_price'];
                }
            }
        }
        unset($item);

        Session::put(self::SESSION_KEY, $items);
    }

    /**
     * Ubah siklus tagihan untuk item produk (harga ikut dihitung ulang
     * dari harga produk saat ini — bukan harga snapshot lama).
     */
    public function updateProductCycle(string $key, string $cycle): void
    {
        $items = $this->items();

        foreach ($items as &$item) {
            if ($item['key'] === $key && $item['type'] === 'product') {
                $product = Product::find($item['product_id']);
                $price = $product?->priceForCycle($cycle);

                if ($price !== null) {
                    $item['billing_cycle'] = $cycle;
                    $item['price'] = $price;
                }
            }
        }
        unset($item);

        Session::put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    private function push(array $item): void
    {
        $items = $this->items();
        $items[] = $item;

        Session::put(self::SESSION_KEY, $items);
    }
}
