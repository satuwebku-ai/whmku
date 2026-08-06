<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'company', 'address',
        'city', 'state', 'postal_code', 'country', 'password', 'status', 'internal_notes',
        'email_verified_at', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token', 'internal_notes'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'reset_code_expires_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'notify_promo' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Buat kode reset 6 digit, simpan hash-nya, kembalikan kode aslinya
     * untuk dikirim lewat email.
     */
    public function generateResetCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'reset_code_hash' => \Illuminate\Support\Facades\Hash::make($code),
            'reset_code_expires_at' => now()->addMinutes(15),
            'reset_attempts' => 0,
        ])->save();

        return $code;
    }

    public function resetCodeIsValid(string $code): bool
    {
        if (! $this->reset_code_hash || ! $this->reset_code_expires_at) {
            return false;
        }

        if ($this->reset_code_expires_at->isPast()) {
            return false;
        }

        return \Illuminate\Support\Facades\Hash::check($code, $this->reset_code_hash);
    }

    public function clearResetCode(): void
    {
        $this->forceFill([
            'reset_code_hash' => null,
            'reset_code_expires_at' => null,
            'reset_attempts' => 0,
        ])->save();
    }

    /**
     * Nomor tujuan notifikasi WhatsApp. Dipakai otomatis oleh channel
     * WhatsApp — jatuh ke nomor telepon biasa kalau kolom khususnya kosong.
     */
    public function routeNotificationForWhatsApp(): ?string
    {
        return $this->whatsapp_number ?: $this->phone;
    }

    public function hostingAccounts(): HasMany
    {
        return $this->hasMany(HostingAccount::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->name));
        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

        return $initials ?: 'NA';
    }

    public function getAvatarUrlAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=6366F1&color=fff';
    }

    /**
     * Klien nonaktif tidak boleh login ke client area.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
