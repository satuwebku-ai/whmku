<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'server_id', 'domain', 'package', 'server', 'panel',
        'username', 'price', 'billing_cycle', 'status', 'next_due_date',
        'provision_status', 'provision_message', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'next_due_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function serverModel(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
