<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class LowStockProducts extends BaseWidget
{
    protected static ?string $heading = 'Productos con Stock Crítico';
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('stock', '<=', DB::raw('min_stock'))
                    ->orderBy('stock')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->color(fn ($record) => $record->stock <= $record->min_stock ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Mínimo')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('reorder')
                    ->label('Reordenar')
                    ->icon('heroicon-m-shopping-cart')
                    ->url(fn (Product $record) => route('filament.admin.resources.products.edit', $record))
            ]);
    }
}