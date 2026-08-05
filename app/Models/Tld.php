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
            'is_active' => 'boolean',
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
     * Apakah harga modal sudah terisi? Markup hanya bisa dihitung
     * kalau nilai ini tersedia.
     */
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
