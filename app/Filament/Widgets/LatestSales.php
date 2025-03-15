<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSales extends BaseWidget
{
    protected static ?string $heading = 'Últimas Ventas';
    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'xl' => 'full',
    ];
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('usd')
                    ->label('Total')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Tipo de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'credit' => 'Crédito',
                        'card' => 'Tarjeta',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->url(fn (Sale $record) => route('filament.resources.sales.view', $record))
            ]);
    }
}