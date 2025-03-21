<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'identification',
        'name',
        'phone',
        'email',
        'address',
        'references',
        'current_balance'
    ];

    protected $casts = [
        'current_balance' => 'decimal:2'
    ];

    public function availableCredit(): float
    {
        return $this->credit_limit - $this->current_balance;
    }

    public function hasAvailableCredit(float $amount): bool
    {
        return $this->availableCredit() >= $amount;
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function pendingSales(): HasMany
    {
        return $this->hasMany(Sale::class)->where('status', 'pending');
    }
}
