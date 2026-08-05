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
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
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
