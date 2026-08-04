<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'content', 'meta_title', 'meta_description',
        'meta_keywords', 'og_image', 'noindex', 'is_published',
        'show_in_footer', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'noindex' => 'boolean',
            'is_published' => 'boolean',
            'show_in_footer' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if (blank($page->slug)) {
                $page->slug = static::uniqueSlug($page->title, $page->id);
            } else {
                $page->slug = Str::slug($page->slug);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'halaman';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Judul untuk tag <title> — pakai meta_title kalau diisi.
     */
    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description
            ?: Str::limit(strip_tags((string) $this->content), 155);
    }

    public function getUrlAttribute(): string
    {
        return url('/p/' . $this->slug);
    }
}
