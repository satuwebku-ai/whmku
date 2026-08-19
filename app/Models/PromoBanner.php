<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoBanner extends Model
{
    /**
     * Halaman publik yang bisa dipilih sebagai tujuan tampil banner ini.
     * "all" sengaja jadi bawaan (dan nilai default banner lama sebelum
     * fitur ini ada) -- supaya banner yang sudah dibuat tidak tiba-tiba
     * hilang begitu migrasi ini dijalankan.
     */
    public const PAGES = [
        'all' => 'Semua Halaman',
        'home' => 'Beranda',
        'catalog' => 'Katalog Hosting',
        'domain_search' => 'Cek Domain',
    ];

    protected $fillable = [
        'title', 'subtitle', 'image', 'link_url', 'button_text',
        'open_in_new_tab', 'is_active', 'sort_order', 'display_page', 'starts_at', 'ends_at',
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

    /**
     * Cuma banner yang memang ditujukan untuk halaman ini, atau yang
     * ditujukan untuk "Semua Halaman".
     */
    public function scopeForPage($query, string $page)
    {
        return $query->where(fn ($q) => $q->where('display_page', $page)->orWhere('display_page', 'all'));
    }
}
