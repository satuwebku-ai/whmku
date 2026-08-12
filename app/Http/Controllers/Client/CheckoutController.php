<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tld;
use App\Services\Cart\CartService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(CartService $cart): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang Anda masih kosong.');
        }

        $subtotal = $cart->subtotal();
        $coupon = $this->sessionCoupon();
        $discount = $coupon ? $coupon->calculateDiscount($subtotal) : 0;

        return view('client.checkout.index', [
            'items'    => collect($cart->items()),
            'subtotal' => $subtotal,
            'coupon'   => $coupon,
            'discount' => $discount,
            'client'   => Auth::guard('client')->user(),
            'issues'   => $this->validateCart($cart),
        ]);
    }

    /**
     * Terapkan kode kupon ke sesi checkout — belum menyentuh invoice
     * apapun, hanya disimpan sementara sampai order benar-benar dibuat.
     */
    public function applyCoupon(Request $request, CartService $cart): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);

        $coupon = Coupon::where('code', strtoupper(trim($data['code'])))->first();

        if (! $coupon) {
            return back()->with('error', 'Kode kupon tidak ditemukan.');
        }

        $client = Auth::guard('client')->user();
        $error = $coupon->validateFor($client, $cart->subtotal());

        if ($error) {
            return back()->with('error', $error);
        }

        session(['checkout.coupon_id' => $coupon->id]);

        return back()->with('success', "Kupon {$coupon->code} berhasil diterapkan.");
    }

    public function removeCoupon(): RedirectResponse
    {
        session()->forget('checkout.coupon_id');

        return back()->with('success', 'Kupon dibatalkan.');
    }

    private function sessionCoupon(): ?Coupon
    {
        $id = session('checkout.coupon_id');

        return $id ? Coupon::find($id) : null;
    }

    public function store(CartService $cart): RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang Anda masih kosong.');
        }

        $issues = $this->validateCart($cart);

        if ($issues) {
            return redirect()->route('client.checkout')->with('error', implode(' ', $issues));
        }

        /** @var Client $client */
        $client = Auth::guard('client')->user();

        // Divalidasi ulang di sini (bukan cuma saat diterapkan) karena isi
        // keranjang atau batas pemakaian kupon bisa berubah di antara
        // waktu kupon diterapkan dan tombol bayar ditekan.
        $coupon = $this->sessionCoupon();

        if ($coupon) {
            $error = $coupon->validateFor($client, $cart->subtotal());

            if ($error) {
                session()->forget('checkout.coupon_id');

                return redirect()->route('client.checkout')->with('error', "Kupon tidak bisa dipakai: {$error}");
            }
        }

        $invoice = DB::transaction(function () use ($cart, $client, $coupon) {
            $invoice = Invoice::create([
                'client_id'  => $client->id,
                'amount'     => 0,
                'tax'        => 0,
                'discount'   => 0,
                'status'     => 'unpaid',
                'issue_date' => now(),
                'due_date'   => now()->addDays(3),
            ]);

            $total = 0;

            foreach ($cart->items() as $item) {
                foreach ($this->buildLinesForItem($client, $item) as $line) {
                    InvoiceItem::create([
                        'invoice_id'  => $invoice->id,
                        'order_id'    => $line['order']->id,
                        'description' => $line['description'],
                        'amount'      => $line['amount'],
                    ]);

                    $total += $line['amount'];
                }
            }

            $discount = $coupon ? $coupon->calculateDiscount($total) : 0;

            $invoice->update([
                'amount'    => $total,
                'coupon_id' => $coupon?->id,
                'discount'  => $discount,
            ]);

            $coupon?->increment('usage_count');

            return $invoice;
        });

        $cart->clear();
        session()->forget('checkout.coupon_id');

        return redirect()->route('client.invoices.show', $invoice)->with(
            'success',
            "Pesanan {$invoice->invoice_number} berhasil dibuat! Pilih metode pembayaran di bawah untuk mengaktifkan layanan Anda."
        );
    }

    /**
     * Validasi yang HARUS lolos sebelum checkout diproses — saat ini
     * cuma satu: domain yang mau didaftarkan baru harus punya ekstensi
     * yang benar-benar dijual (ada harganya), karena form produk di
     * Fase 7b menerima nama domain bebas teks tanpa validasi TLD.
     *
     * @return string[]
     */
    private function validateCart(CartService $cart): array
    {
        $issues = [];

        foreach ($cart->items() as $item) {
            if ($item['type'] === 'product' && ($item['domain_mode'] ?? null) === 'register') {
                $domainName = $item['domain_name'] ?? '';

                if (! $this->resolveTld($domainName)) {
                    $issues[] = "Domain \"{$domainName}\" pada paket \"{$item['name']}\" tidak bisa diproses (ekstensi belum dijual atau nama domain tidak lengkap). Hapus item ini dari keranjang dan tambahkan ulang tanpa opsi \"Daftarkan domain baru\", atau pilih domain lain.";
                }
            }
        }

        return $issues;
    }

    private function resolveTld(string $domainName): ?Tld
    {
        if (! str_contains($domainName, '.')) {
            return null;
        }

        $ext = '.' . Str::after($domainName, '.');

        return Tld::where('extension', $ext)->where('is_active', true)->first();
    }

    /**
     * Satu item keranjang bisa menghasilkan lebih dari satu baris invoice —
     * mis. hosting yang dibundel dengan pendaftaran domain baru menjadi
     * 2 order (hosting + domain) tapi tetap 1 invoice.
     *
     * @return array<int, array{order: Order, amount: float, description: string}>
     */
    private function buildLinesForItem(Client $client, array $item): array
    {
        if ($item['type'] === 'domain') {
            return [$this->buildStandaloneDomainLine($client, $item)];
        }

        $lines = [$this->buildHostingLine($client, $item)];

        if (($item['domain_mode'] ?? null) === 'register' && filled($item['domain_name'] ?? null)) {
            $tld = $this->resolveTld($item['domain_name']);

            if ($tld) {
                $lines[] = $this->buildBundledDomainLine($client, $item['domain_name'], $tld);
            }
        }

        return $lines;
    }

    private function buildHostingLine(Client $client, array $item): array
    {
        $product = ! empty($item['product_id']) ? Product::with('server')->find($item['product_id']) : null;
        $domainName = $item['domain_name'] ?? null;

        // Auto-provisioning butuh DUA hal: server tujuan DAN nama paket WHM
        // yang persis sama dengan yang ada di server itu. Server saja tidak
        // cukup — kalau nama paketnya kosong, memaksa tetap mencoba hanya
        // akan gagal dengan error mentah dari WHM ("package not found")
        // yang membingungkan. Diperlakukan sama seperti "belum diatur sama
        // sekali", jatuh ke mode manual dengan pesan yang jelas.
        $readyForAutoProvision = $product?->server_id && filled($product?->panel_package);

        $hostingAccount = HostingAccount::create([
            'client_id'        => $client->id,
            'product_id'       => $product?->id,
            'server_id'        => $readyForAutoProvision ? $product->server_id : null,
            'domain'           => $domainName ?: ('layanan-' . Str::lower(Str::random(6))),
            'package'          => $product?->panel_package ?: ($product?->name ?? $item['name']),
            'panel'            => $product?->server?->panel ?? 'cpanel',
            'price'            => $item['price'],
            'billing_cycle'    => $item['billing_cycle'],
            'status'           => 'pending',
            'provision_status' => 'manual',
            'provision_message' => $readyForAutoProvision
                ? null
                : ($product?->server_id ? 'Nama paket WHM belum diatur di produk ini — aktivasi perlu dilakukan manual oleh admin.' : null),
            'next_due_date'    => $this->nextDueDate($item['billing_cycle']),
        ]);

        $order = Order::create([
            'client_id'          => $client->id,
            'product_id'         => $product?->id,
            'hosting_account_id' => $hostingAccount->id,
            'product_name'       => $item['name'],
            'order_type'         => 'hosting',
            'amount'             => $item['price'],
            'status'             => 'pending',
        ]);

        $cycleLabel = Product::CYCLES[$item['billing_cycle']] ?? $item['billing_cycle'];

        return ['order' => $order, 'amount' => (float) $item['price'], 'description' => "{$item['name']} ({$cycleLabel})"];
    }

    private function buildBundledDomainLine(Client $client, string $domainName, Tld $tld): array
    {
        $domain = Domain::create([
            'client_id'        => $client->id,
            'registrar_id'     => $tld->registrar_id,
            'tld_id'           => $tld->id,
            'domain_name'      => $domainName,
            'price'            => $tld->register_price,
            'years'            => 1,
            'status'           => 'pending',
            'provision_status' => 'manual',
            // Domain bundel (gratis, ikut paket hosting) tidak melalui
            // halaman Keranjang tempat klien bisa memilih, jadi default
            // dinyalakan — sama seperti default checkbox di keranjang.
            'whois_privacy'    => true,
        ]);

        $order = Order::create([
            'client_id'    => $client->id,
            'product_name' => "Registrasi Domain {$domainName}",
            'order_type'   => 'domain',
            'amount'       => $tld->register_price,
            'status'       => 'pending',
        ]);

        // Domain::order() adalah belongsTo lewat kolom domains.order_id
        // (Fase 4) — order_id BARU diketahui setelah $order dibuat, jadi
        // domain-nya diisi belakangan di sini, bukan saat Domain::create().
        $domain->update(['order_id' => $order->id]);

        return ['order' => $order, 'amount' => (float) $tld->register_price, 'description' => "Registrasi Domain {$domainName} (1 tahun)"];
    }

    private function buildStandaloneDomainLine(Client $client, array $item): array
    {
        $tld = Tld::find($item['tld_id'] ?? null);
        $years = (int) ($item['years'] ?? 1);

        $domain = Domain::create([
            'client_id'        => $client->id,
            'registrar_id'     => $tld?->registrar_id,
            'tld_id'           => $item['tld_id'] ?? null,
            'domain_name'      => $item['domain_name'],
            'price'            => $item['price'],
            'years'            => $years,
            'status'           => 'pending',
            'provision_status' => 'manual',
            'whois_privacy'    => (bool) ($item['whois_privacy'] ?? false),
        ]);

        $order = Order::create([
            'client_id'    => $client->id,
            'product_name' => "Registrasi Domain {$item['domain_name']}",
            'order_type'   => 'domain',
            'amount'       => $item['price'],
            'status'       => 'pending',
        ]);

        $domain->update(['order_id' => $order->id]);

        $hasPaidPrivacy = ($item['whois_privacy'] ?? false) && ($item['whois_privacy_price'] ?? 0) > 0;
        $description = "Registrasi Domain {$item['domain_name']} ({$years} tahun)"
            . ($hasPaidPrivacy ? ' + ID Protection' : '');

        return ['order' => $order, 'amount' => (float) $item['price'], 'description' => $description];
    }

    private function nextDueDate(string $cycle): Carbon
    {
        return match ($cycle) {
            'monthly'       => now()->addMonth(),
            'quarterly'     => now()->addMonths(3),
            'semi_annually' => now()->addMonths(6),
            'annually'      => now()->addYear(),
            default         => now()->addMonth(),
        };
    }
}
