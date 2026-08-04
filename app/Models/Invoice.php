<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'unpaid' && $this->due_date?->isPast();
    }
}
