<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
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

        return view('client.checkout.index', [
            'items'    => collect($cart->items()),
            'subtotal' => $cart->subtotal(),
            'client'   => Auth::guard('client')->user(),
            'issues'   => $this->validateCart($cart),
        ]);
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

        $invoice = DB::transaction(function () use ($cart, $client) {
            $invoice = Invoice::create([
                'client_id'  => $client->id,
                'amount'     => 0,
                'tax'        => 0,
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

            $invoice->update(['amount' => $total]);

            return $invoice;
        });

        $cart->clear();

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

        $hostingAccount = HostingAccount::create([
            'client_id'        => $client->id,
            'server_id'        => $product?->server_id,
            'domain'           => $domainName ?: ('layanan-' . Str::lower(Str::random(6))),
            'package'          => $product?->panel_package ?: ($product?->name ?? $item['name']),
            'panel'            => $product?->server?->panel ?? 'cpanel',
            'price'            => $item['price'],
            'billing_cycle'    => $item['billing_cycle'],
            'status'           => 'pending',
            'provision_status' => 'manual',
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
        ]);

        $order = Order::create([
            'client_id'    => $client->id,
            'product_name' => "Registrasi Domain {$item['domain_name']}",
            'order_type'   => 'domain',
            'amount'       => $item['price'],
            'status'       => 'pending',
        ]);

        $domain->update(['order_id' => $order->id]);

        return ['order' => $order, 'amount' => (float) $item['price'], 'description' => "Registrasi Domain {$item['domain_name']} ({$years} tahun)"];
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
