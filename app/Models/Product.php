<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'min_stock',
        'category_id'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function needsRestock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    public function decrementStock(int $quantity): void
    {
        if ($this->stock < $quantity) {
            throw new \Exception("Stock insuficiente para {$this->name}");
        }
        $this->decrement('stock', $quantity);
    }

    public function hasEnoughStock(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }
}
