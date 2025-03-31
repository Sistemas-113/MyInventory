<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'installment_number',
        'amount',
        'due_date',
        'status',
        'paid_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date'
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now()
        ]);
    }

    public function markAsOverdue(): void
    {
        if ($this->status === 'pending' && $this->due_date->isPast()) {
            $this->update(['status' => 'overdue']);
        }
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        $remaining = $this->amount - $this->total_paid;
        
        if ($this->total_paid >= $this->amount && $this->status !== 'paid') {
            $this->update([
                'status' => 'paid',
                'paid_date' => now()
            ]);
        } elseif ($this->total_paid > 0 && $this->total_paid < $this->amount && $this->status === 'pending') {
            $this->update(['status' => 'pending']);
        }
        
        return max(0, $remaining);
    }

    public function getPaymentProgressAttribute(): float
    {
        return $this->amount > 0 ? ($this->total_paid / $this->amount) * 100 : 0;
    }

    public function getFormattedStatusAttribute(): string
    {
        $totalPaid = $this->total_paid;
        $remaining = $this->remaining_amount;
        
        if ($totalPaid > 0 && $remaining > 0) {
            return "Abonado: $ " . number_format($totalPaid, 0, ',', '.') . 
                   " | Resta: $ " . number_format($remaining, 0, ',', '.');
        }
        
        return $this->status === 'paid' ? 'Pagada' : 'Pendiente';
    }

    public function applyPayment(float $amount, string $paymentMethod = 'payment', ?string $notes = null): float
    {
        if ($amount <= 0) return 0;

        DB::transaction(function () use ($amount, $paymentMethod, $notes) {
            $remainingForThisInstallment = $this->remaining_amount;
            
            if ($amount <= $remainingForThisInstallment) {
                $this->payments()->create([
                    'sale_id' => $this->sale_id,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'payment_date' => now(),
                    'notes' => $notes ?? "Abono a cuota {$this->installment_number}"
                ]);
                
                $this->refresh();
                $this->updateInstallmentStatus();
                
                return 0;
            }
            
            $this->payments()->create([
                'sale_id' => $this->sale_id,
                'amount' => $remainingForThisInstallment,
                'payment_method' => $paymentMethod,
                'payment_date' => now(),
                'notes' => $notes ?? "Pago completo de cuota {$this->installment_number}"
            ]);
            
            $this->refresh();
            $this->updateInstallmentStatus();

            $excess = $amount - $remainingForThisInstallment;

            if ($excess > 0) {
                $nextInstallments = $this->sale->installments()
                    ->where('status', '!=', 'paid')
                    ->where('installment_number', '>', $this->installment_number)
                    ->orderBy('installment_number')
                    ->get();

                foreach ($nextInstallments as $nextInstallment) {
                    $nextRemaining = $nextInstallment->remaining_amount;
                    $amountToApply = min($excess, $nextRemaining);

                    if ($amountToApply > 0) {
                        $nextInstallment->payments()->create([
                            'sale_id' => $this->sale_id,
                            'amount' => $amountToApply,
                            'payment_method' => $paymentMethod,
                            'payment_date' => now(),
                            'notes' => "Excedente de cuota {$this->installment_number}"
                        ]);

                        $nextInstallment->refresh();
                        $nextInstallment->updateInstallmentStatus();

                        $excess -= $amountToApply;
                    }

                    if ($excess <= 0) break;
                }
            }

            return $excess;
        });
    }

    protected function updateInstallmentStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        
        $this->total_paid = $totalPaid;
        
        if ($totalPaid >= $this->amount) {
            $this->status = 'paid';
            $this->paid_date = now();
        } elseif ($totalPaid > 0) {
            $this->status = $this->due_date < now() ? 'overdue' : 'pending';
        } else {
            $this->status = $this->due_date < now() ? 'overdue' : 'pending';
        }
        
        $this->save();
    }

    protected function calculateAndDistributePayment(float $amount): void 
    {
        $amount = round($amount, 2);
        $remaining = round($this->remaining_amount, 2);
        
        if ($amount > $remaining) {
            $this->payments()->create([
                'sale_id' => $this->sale_id,
                'amount' => $remaining,
                'payment_method' => 'payment',
                'notes' => "Pago completo de cuota {$this->installment_number}"
            ]);
            
            $this->update([
                'status' => 'paid',
                'paid_date' => now()
            ]);
            
            $excess = round($amount - $remaining, 2);
            
            if ($excess > 0) {
                $nextInstallments = $this->sale->installments()
                    ->where('status', 'pending')
                    ->where('installment_number', '>', $this->installment_number)
                    ->orderBy('installment_number')
                    ->get();
                
                foreach ($nextInstallments as $nextInstallment) {
                    $installmentRemaining = round($nextInstallment->remaining_amount, 2);
                    $amountToApply = min($excess, $installmentRemaining);
                    
                    if ($amountToApply > 0) {
                        $nextInstallment->payments()->create([
                            'sale_id' => $this->sale_id,
                            'amount' => $amountToApply,
                            'payment_method' => 'payment',
                            'notes' => "Abono de excedente a cuota {$nextInstallment->installment_number}"
                        ]);
                        
                        $excess -= $amountToApply;
                        
                        if ($amountToApply >= $installmentRemaining) {
                            $nextInstallment->update([
                                'status' => 'paid',
                                'paid_date' => now()
                            ]);
                        }
                    }
                    
                    if ($excess <= 0) break;
                }
            }
        } else {
            $this->payments()->create([
                'sale_id' => $this->sale_id,
                'amount' => $amount,
                'payment_method' => 'payment',
                'notes' => "Abono a cuota {$this->installment_number}"
            ]);
            
            if ($this->total_paid >= $this->amount) {
                $this->update([
                    'status' => 'paid',
                    'paid_date' => now()
                ]);
            }
        }
    }

    public static function distributeExcessPayment(Sale $sale, float $excessAmount): void
    {
        if ($excessAmount <= 0) return;

        $pendingInstallments = $sale->installments()
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->get();

        foreach ($pendingInstallments as $installment) {
            $excessAmount = $installment->applyPayment($excessAmount);
            
            if ($excessAmount <= 0) break;
        }
    }
}
