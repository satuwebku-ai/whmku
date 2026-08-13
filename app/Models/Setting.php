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

        return static::cachedRows()->map(fn ($row) => static::decryptRow($row))->toArray();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::decryptRow(static::cachedRows()->get($key));

        return $value ?? $default;
    }

    /**
     * $encrypted default-nya SENGAJA `false` — supaya SEMUA pemanggilan
     * put() yang sudah ada di seluruh aplikasi (puluhan tempat) tetap
     * berperilaku identik seperti sebelumnya, tidak ada yang tiba-tiba
     * ikut terenkripsi tanpa diminta.
     */
    public static function put(string $key, mixed $value, string $group = 'general', bool $encrypted = false): void
    {
        static::updateOrCreate(['key' => $key], [
            'value' => ($encrypted && filled($value)) ? encrypt($value) : $value,
            'group' => $group,
            'is_encrypted' => $encrypted,
        ]);
    }

    /**
     * Semua baris (bukan cuma value-nya) di-cache sekali, dipakai
     * bersama oleh get() & all() supaya keduanya konsisten — dan
     * supaya is_encrypted ikut terbawa untuk didekripsi.
     */
    private static function cachedRows(): \Illuminate\Support\Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->get(['key', 'value', 'is_encrypted'])->keyBy('key'));
    }

    private static function decryptRow($row): mixed
    {
        if (! $row) {
            return null;
        }

        if ($row->is_encrypted && filled($row->value)) {
            try {
                return decrypt($row->value);
            } catch (\Throwable $e) {
                // Ciphertext rusak atau APP_KEY pernah berubah -- lebih
                // aman kembalikan kosong daripada melempar error yang
                // bisa merusak seluruh halaman yang memanggil Setting::get().
                return null;
            }
        }

        return $row->value;
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
