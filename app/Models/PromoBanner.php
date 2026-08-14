<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoBanner extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'image', 'link_url', 'button_text',
        'open_in_new_tab', 'is_active', 'sort_order', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    /**
     * Aktif DAN dalam rentang tanggal tayang (kalau diisi) — dipakai di
     * halaman publik supaya banner yang jadwalnya belum mulai atau sudah
     * lewat otomatis tidak tampil, tanpa admin perlu matikan manual.
     */
    public function scopeLive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()));
    }
}
