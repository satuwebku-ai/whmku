<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'type', 'value', 'min_order', 'max_discount',
        'usage_limit', 'usage_count', 'usage_limit_per_client',
        'starts_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'starts_at' => 'date',
            'expires_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon) {
            $coupon->code = strtoupper(trim($coupon->code));
        });
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Periksa apakah kupon ini boleh dipakai klien tertentu untuk subtotal
     * tertentu. Mengembalikan pesan error kalau tidak valid, atau null
     * kalau valid — dipilih daripada exception supaya mudah ditampilkan
     * langsung sebagai pesan form.
     */
    public function validateFor(Client $client, float $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'Kupon ini sudah tidak aktif.';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Kupon ini belum bisa dipakai.';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Kupon ini sudah kedaluwarsa.';
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return 'Kupon ini sudah mencapai batas pemakaian.';
        }

        if ($subtotal < (float) $this->min_order) {
            return 'Minimal transaksi untuk kupon ini adalah Rp ' . number_format((float) $this->min_order, 0, ',', '.') . '.';
        }

        $usedByClient = $this->invoices()
            ->where('client_id', $client->id)
            ->whereIn('status', ['paid', 'unpaid', 'overdue'])
            ->count();

        if ($usedByClient >= $this->usage_limit_per_client) {
            return 'Anda sudah memakai kupon ini sebelumnya.';
        }

        return null;
    }

    /**
     * Hitung nominal potongan untuk subtotal tertentu.
     */
    public function calculateDiscount(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        // Diskon tidak pernah melebihi subtotal itu sendiri.
        return round(min($discount, $subtotal), 2);
    }

    public function getValueLabelAttribute(): string
    {
        return $this->type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.') . '%'
            : 'Rp ' . number_format((float) $this->value, 0, ',', '.');
    }
}
