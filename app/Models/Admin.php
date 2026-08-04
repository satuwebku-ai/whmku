<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'role',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'two_factor_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'two_factor_enabled' => 'boolean',
            'last_login_at'      => 'datetime',
            'otp_expires_at'     => 'datetime',
            'password'           => 'hashed',
        ];
    }

    /**
     * Buat OTP 6 digit, simpan hash-nya, kembalikan kode aslinya
     * untuk dikirim lewat email. Kode mentah tidak pernah disimpan.
     */
    public function generateOtp(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'otp_code_hash'  => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts'   => 0,
        ])->save();

        return $code;
    }

    public function otpIsValid(string $code): bool
    {
        if (! $this->otp_code_hash || ! $this->otp_expires_at) {
            return false;
        }

        if ($this->otp_expires_at->isPast()) {
            return false;
        }

        return Hash::check($code, $this->otp_code_hash);
    }

    public function clearOtp(): void
    {
        $this->forceFill([
            'otp_code_hash'  => null,
            'otp_expires_at' => null,
            'otp_attempts'   => 0,
        ])->save();
    }

    /**
     * Login pakai kolom "username", bukan "email".
     */
    public function username(): string
    {
        return 'username';
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Fallback: avatar inisial via ui-avatars
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=6366F1&color=fff';
    }
}
