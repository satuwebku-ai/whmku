<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'product_id', 'server_id', 'domain', 'package', 'server', 'panel',
        'username', 'price', 'billing_cycle', 'status', 'next_due_date',
        'provision_status', 'provision_message', 'client_details', 'internal_notes',
        'cancellation_status', 'cancellation_reason', 'cancellation_requested_at',
        'cancellation_admin_note', 'renewal_invoice_id',
        'pending_upgrade_product_id', 'pending_upgrade_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'next_due_date' => 'date',
            'cancellation_requested_at' => 'datetime',
            'client_details' => 'encrypted',
        ];
    }

    public function hasPendingCancellation(): bool
    {
        return $this->cancellation_status === 'requested';
    }

    public function renewalInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'renewal_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function pendingUpgradeProduct(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'pending_upgrade_product_id');
    }

    public function pendingUpgradeInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'pending_upgrade_invoice_id');
    }

    /**
     * Paket lain yang boleh jadi tujuan upgrade mandiri klien. Dibatasi
     * dengan sengaja supaya tidak ada kombinasi yang berujung error atau
     * butuh campur tangan admin:
     *   - kategori produk sama (upgrade hosting ke hosting, bukan ke domain)
     *   - server sama (pindah paket lewat WHM `changepackage` hanya bisa
     *     di server yang sama; pindah ANTAR server itu migrasi akun penuh,
     *     operasi yang jauh lebih berisiko dan di luar cakupan fitur ini)
     *   - harga siklus yang sama lebih tinggi (downgrade tidak ditangani
     *     di sini karena butuh skema refund/kredit yang belum dibangun)
     */
    public function upgradeEligibleProducts()
    {
        if (! $this->product_id || ! $this->product) {
            return \App\Models\Product::whereRaw('1 = 0')->get(); // kosong — akun lama tanpa jejak produk
        }

        return \App\Models\Product::where('product_category_id', $this->product->product_category_id)
            ->where('server_id', $this->server_id)
            ->where('is_active', true)
            ->where('id', '!=', $this->product_id)
            ->get()
            ->filter(function ($p) {
                $newPrice = $p->priceForCycle($this->billing_cycle);

                return $newPrice !== null && $newPrice > (float) $this->price;
            })
            ->values();
    }

    /**
     * Selisih biaya prorata untuk upgrade ke produk tertentu — hanya
     * menghitung sisa hari sampai next_due_date, BUKAN menagih ulang
     * dari awal siklus. Mulai siklus berikutnya, tagihan otomatis
     * memakai harga baru (lihat renewalAmount()).
     */
    public function prorateUpgrade(\App\Models\Product $newProduct): float
    {
        $cycleDays = match ($this->billing_cycle) {
            'quarterly' => 90,
            'semi_annually' => 180,
            'annually' => 365,
            default => 30,
        };

        $remainingDays = $this->next_due_date
            ? max(0, min($cycleDays, (int) now()->startOfDay()->diffInDays($this->next_due_date, false)))
            : $cycleDays;

        $newPrice = (float) $newProduct->priceForCycle($this->billing_cycle);
        $oldDailyRate = (float) $this->price / $cycleDays;
        $newDailyRate = $newPrice / $cycleDays;

        return round(($newDailyRate - $oldDailyRate) * $remainingDays);
    }

    /**
     * Nominal satu siklus perpanjangan, sesuai billing_cycle layanan ini.
     */
    public function renewalAmount(): float
    {
        return (float) $this->price;
    }

    /**
     * Tanggal jatuh tempo berikutnya setelah siklus ini lunas.
     */
    public function nextCycleDate(): \Carbon\Carbon
    {
        $base = $this->next_due_date ?: now();

        return match ($this->billing_cycle) {
            'quarterly' => $base->copy()->addMonths(3),
            'semi_annually' => $base->copy()->addMonths(6),
            'annually' => $base->copy()->addYear(),
            default => $base->copy()->addMonth(),
        };
    }

    public function cycleLabel(): string
    {
        return match ($this->billing_cycle) {
            'quarterly' => '3 bulan',
            'semi_annually' => '6 bulan',
            'annually' => '1 tahun',
            default => 'bulanan',
        };
    }

    /**
     * Buat invoice perpanjangan untuk layanan ini. Dipakai DUA tempat:
     * perintah terjadwal (lumora:generate-renewal-invoices) untuk H-7
     * otomatis, dan tombol "Perpanjang Sekarang" klien untuk permintaan
     * manual kapan saja. Disatukan di sini supaya logikanya tidak pernah
     * berbeda antara jalur otomatis dan jalur manual.
     */
    public function createRenewalInvoice(): \App\Models\Invoice
    {
        $amount = $this->renewalAmount();

        $invoice = \App\Models\Invoice::create([
            'client_id' => $this->client_id,
            'amount' => $amount,
            'tax' => 0,
            'discount' => 0,
            'status' => 'unpaid',
            'issue_date' => now(),
            'due_date' => $this->next_due_date ?: now()->addDays(7),
        ]);

        \App\Models\InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Perpanjangan Hosting — {$this->domain} ({$this->package}, {$this->cycleLabel()})",
            'amount' => $amount,
        ]);

        $this->update(['renewal_invoice_id' => $invoice->id]);

        return $invoice;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function serverModel(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
