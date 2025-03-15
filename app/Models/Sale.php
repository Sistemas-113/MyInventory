<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'total_amount',
        'payment_type',
        'status',
        'interest_rate',
        'installments',
        'first_payment_date'
    ];

    // Asegurar que los campos numéricos se manejen correctamente
    protected $casts = [
        'total_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'first_payment_date' => 'date'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    public static function scopeCredits(Builder $query): Builder
    {
        return $query->where('payment_type', 'credit');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function paidInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)->where('status', 'paid');
    }

    public function getNextPaymentDateAttribute()
    {
        return $this->hasMany(Installment::class)
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first()?->due_date;
    }

    public function getRemainingAmountAttribute(): float
    {
        $paidAmount = $this->installments()->where('status', 'paid')->sum('amount');
        return $this->total_amount - $paidAmount;
    }

    protected function calculateTotal(): float
    {
        $subtotal = $this->details()->sum(DB::raw('quantity * unit_price'));
        
        if ($this->payment_type === 'credit' && $this->interest_rate > 0) {
            $interest = ($subtotal * $this->interest_rate) / 100;
            return $subtotal + $interest;
        }
        
        return $subtotal;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($sale) {
            if (!$sale->total_amount) {
                $sale->total_amount = $sale->calculateTotal();
            }
        });
    }
}
