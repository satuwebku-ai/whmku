<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tld extends Model
{
    use HasFactory;

    protected $fillable = [
        'extension', 'registrar_id', 'register_price', 'renew_price',
        'transfer_price', 'min_years', 'max_years', 'is_active',
        'cost_register', 'cost_renew', 'cost_transfer', 'cost_currency', 'cost_synced_at',
        'year_prices', 'year_renew_prices',
        'show_in_search', 'search_group', 'search_order', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'register_price' => 'decimal:2',
            'renew_price' => 'decimal:2',
            'transfer_price' => 'decimal:2',
            'cost_register' => 'decimal:2',
            'cost_renew' => 'decimal:2',
            'cost_transfer' => 'decimal:2',
            'cost_synced_at' => 'datetime',
            'year_prices' => 'array',
            'year_renew_prices' => 'array',
            'is_active' => 'boolean',
            'show_in_search' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(Registrar::class);
    }

    /**
     * TLD yang tampil di pencarian domain publik.
     *
     * Selain harus aktif, harga jualnya juga wajib sudah terisi — TLD
     * berharga Rp 0 kalau ikut tampil akan terjual gratis.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('register_price', '>', 0);
    }

    /**
     * Ekstensi yang ditampilkan di halaman Cek Domain publik.
     *
     * Terpisah dari scopeActive: sebuah TLD bisa aktif dijual (mis. lewat
     * pesanan manual) tanpa perlu ikut memenuhi halaman pencarian.
     */
    public function scopeVisibleInSearch(Builder $query): Builder
    {
        return $query->active()->where('show_in_search', true);
    }

    public function getSearchGroupLabelAttribute(): string
    {
        return $this->search_group ?: 'Lainnya';
    }

    /**
     * Apakah harga modal sudah terisi? Markup hanya bisa dihitung
     * kalau nilai ini tersedia.
     */
    /**
     * Harga registrasi untuk durasi tertentu.
     *
     * Kalau ada harga khusus per tahun, itu yang dipakai. Kalau tidak,
     * harga dihitung linier dari harga 1 tahun — perilaku standar dan
     * yang paling tidak mengejutkan pelanggan.
     */
    public function priceForYears(int $years, string $type = 'register'): float
    {
        $overrides = $type === 'renew' ? $this->year_renew_prices : $this->year_prices;
        $base = $type === 'renew' ? (float) $this->renew_price : (float) $this->register_price;

        if (is_array($overrides) && isset($overrides[(string) $years]) && (float) $overrides[(string) $years] > 0) {
            return (float) $overrides[(string) $years];
        }

        return $base * max($years, 1);
    }

    /**
     * Harga per tahun untuk durasi tertentu — dipakai menampilkan
     * "Rp x/tahun" saat pelanggan memilih durasi panjang.
     */
    public function pricePerYear(int $years, string $type = 'register'): float
    {
        return $this->priceForYears($years, $type) / max($years, 1);
    }

    /**
     * Apakah durasi ini punya harga khusus (bukan hasil kali linier)?
     */
    public function hasYearOverride(int $years, string $type = 'register'): bool
    {
        $overrides = $type === 'renew' ? $this->year_renew_prices : $this->year_prices;

        return is_array($overrides) && ! empty($overrides[(string) $years]);
    }

    public function hasCost(): bool
    {
        return (float) $this->cost_register > 0;
    }

    public function hasSellingPrice(): bool
    {
        return (float) $this->register_price > 0;
    }

    /**
     * Margin rupiah per registrasi.
     */
    public function getMarginAttribute(): float
    {
        return (float) $this->register_price - (float) $this->cost_register;
    }

    public function getMarginPercentAttribute(): ?float
    {
        if (! $this->hasCost()) {
            return null;
        }

        return round($this->margin / (float) $this->cost_register * 100, 1);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }
}
