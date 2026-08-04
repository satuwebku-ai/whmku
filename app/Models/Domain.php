<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'order_id', 'registrar_id', 'tld_id', 'domain_name',
        'price', 'years', 'status', 'register_date', 'expiry_date',
        'auto_renew', 'whois_privacy', 'nameservers',
        'provision_status', 'provision_message', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'register_date' => 'date',
            'expiry_date' => 'date',
            'auto_renew' => 'boolean',
            'whois_privacy' => 'boolean',
            'nameservers' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(Registrar::class);
    }

    public function tld(): BelongsTo
    {
        return $this->belongsTo(Tld::class);
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->diffInDays(now(), false) > -30 && $this->expiry_date->isFuture();
    }
}
