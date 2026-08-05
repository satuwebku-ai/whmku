<?php

namespace App\Models;

use App\Services\Provisioning\ProvisioningService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'client_id', 'order_id', 'amount', 'tax', 'total',
        'status', 'issue_date', 'due_date', 'paid_at', 'payment_method', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }

            $invoice->total = (float) $invoice->amount + (float) $invoice->tax;
        });

        static::updating(function (Invoice $invoice) {
            if ($invoice->isDirty(['amount', 'tax'])) {
                $invoice->total = (float) $invoice->amount + (float) $invoice->tax;
            }
        });

        /*
         * Titik pemicu TUNGGAL untuk auto-provisioning (Fase 7c). Dipilih
         * lewat event model, bukan dipanggil manual di tiap tempat yang
         * bisa melunasi invoice (webhook Midtrans/Xendit, approve transfer
         * manual, edit manual admin) — supaya tidak ada jalur yang lupa
         * memicu provisioning kalau nanti ditambah cara baru untuk
         * melunasi invoice.
         */
        static::updated(function (Invoice $invoice) {
            if ($invoice->wasChanged('status') && $invoice->status === 'paid') {
                try {
                    app(ProvisioningService::class)->provisionInvoice($invoice);
                } catch (Throwable $e) {
                    // Provisioning gagal TIDAK boleh membatalkan pelunasan invoice
                    // yang sudah tercatat — klien sudah bayar, jadi kegagalan di
                    // sini harus tercatat untuk ditindaklanjuti admin, bukan
                    // membuat request yang sedang berjalan (mis. webhook) error.
                    Log::error('Auto-provisioning gagal total: ' . $e->getMessage(), [
                        'invoice_id' => $invoice->id,
                    ]);
                }
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $last = static::whereYear('created_at', $year)->orderByDesc('id')->first();
        $next = $last ? ((int) Str::afterLast($last->invoice_number, '-') + 1) : 1;

        return "INV-{$year}-" . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Rincian per item — dipakai invoice hasil checkout keranjang yang bisa
     * berisi beberapa order sekaligus. Invoice manual lama (Fase 2) tidak
     * punya baris di sini dan tetap ditampilkan lewat relasi order() tunggal.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'unpaid' && $this->due_date?->isPast();
    }
}
