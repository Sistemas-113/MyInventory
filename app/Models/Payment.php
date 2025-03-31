<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'installment_id',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'payment_date'  // Agregar a fillable
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime'  // Agregar cast
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }
}
