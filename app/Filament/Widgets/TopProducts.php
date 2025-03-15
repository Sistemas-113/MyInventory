<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopProducts extends BaseWidget
{
    protected static ?string $heading = 'Productos Más Vendidos';
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->select('products.*')
                    ->selectRaw('COALESCE(SUM(sale_details.quantity), 0) as total_sold')
                    ->leftJoin('sale_details', 'products.id', '=', 'sale_details.product_id')
                    ->groupBy('products.id')
                    ->orderByDesc('total_sold')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_sold')
                    ->label('Vendidos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('usd')
                    ->label('Precio')
                    ->sortable(),
            ]);
    }
}
