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
        'first_payment_date',
        'initial_payment'
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

    public function pendingInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)
            ->where('status', 'pending')
            ->orderBy('due_date');
    }

    public function nextInstallment()
    {
        return $this->pendingInstallments()->first();
    }

    public function getNextPaymentAmountAttribute()
    {
        return $this->nextInstallment()?->amount ?? 0;
    }

    public function getNextPaymentDateAttribute()
    {
        return $this->nextInstallment()?->due_date;
    }

    public function getRemainingInstallmentsAttribute()
    {
        return $this->pendingInstallments()->count();
    }

    public function paidInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)->where('status', 'paid');
    }

    public function getRemainingAmountAttribute(): string
    {
        $paidAmount = $this->installments()->where('status', 'paid')->sum('amount');
        $remaining = $this->total_amount - $paidAmount;
        return number_format($remaining, 0, ',', '.');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function calculateTotal(): float
    {
        try {
            $subtotal = $this->details()->sum(DB::raw('quantity * unit_price'));
            $initialPayment = floatval($this->initial_payment ?? 0);
            $remaining = max(0, $subtotal - $initialPayment);
            
            // Si es crédito y tiene tasa de interés, calcular interés sobre el remanente
            if ($this->payment_type === 'credit' && $this->interest_rate > 0) {
                $interest = ($remaining * $this->interest_rate) / 100;
                return $remaining + $interest;
            }
            
            return $remaining;
        } catch (\Exception $e) {
            \Log::error('Error calculando total de venta', [
                'sale_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($sale) {
            if (!$sale->total_amount) {
                $sale->total_amount = $sale->calculateTotal();
            }
            // Asegurar que tenga un status por defecto
            if (!$sale->status) {
                $sale->status = 'pending';
            }
        });
    }
}
