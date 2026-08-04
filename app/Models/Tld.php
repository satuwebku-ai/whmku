<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tld extends Model
{
    use HasFactory;

    protected $fillable = [
        'extension', 'registrar_id', 'register_price', 'renew_price',
        'transfer_price', 'min_years', 'max_years', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'register_price' => 'decimal:2',
            'renew_price' => 'decimal:2',
            'transfer_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(Registrar::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }
}
