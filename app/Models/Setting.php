<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'is_encrypted'];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }

    private const CACHE_KEY = 'app_settings';

    protected static function booted(): void
    {
        // Cache dibersihkan setiap ada perubahan supaya nilai di frontend
        // langsung ikut berubah tanpa perlu clear cache manual.
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Semua setting sebagai array [key => value], di-cache.
     */
    public static function all(...$args): mixed
    {
        if ($args) {
            return parent::all(...$args);
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->toArray());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->toArray());

        return $settings[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }

    /**
     * Simpan banyak setting sekaligus dalam satu grup.
     */
    public static function putMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        }

        static::flushCache();
    }
}
