<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'provider_id',
        'identifier_type',
        'identifier',
        'product_name',
        'product_description',
        'purchase_price',
        'unit_price',
        'quantity',
        'subtotal'
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($detail) {
            $detail->quantity = $detail->quantity ?? 1;
            $detail->purchase_price = floatval(preg_replace('/[^0-9.]/', '', $detail->purchase_price));
            $detail->unit_price = floatval(preg_replace('/[^0-9.]/', '', $detail->unit_price));
            $detail->subtotal = $detail->quantity * $detail->unit_price;
        });

        static::updating(function ($detail) {
            $detail->purchase_price = floatval(preg_replace('/[^0-9.]/', '', $detail->purchase_price));
            $detail->unit_price = floatval(preg_replace('/[^0-9.]/', '', $detail->unit_price));
            $detail->subtotal = $detail->quantity * $detail->unit_price;
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class)->withDefault([
            'name' => 'Sin proveedor'
        ]);
    }
}
