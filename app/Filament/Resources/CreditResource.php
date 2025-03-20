<?php

namespace App\Filament\Resources;

use App\Models\Sale;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\CreditResource\Pages;

class CreditResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $label = 'Créditos';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('payment_type', 'credit'))
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => '$ ' . number_format($state, 0, ',', '.'))
                    ->label('Total'),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
                    ->formatStateUsing(fn ($record) => '$ ' . number_format($record->installments()->where('status', 'paid')->sum('amount'), 0, ',', '.'))
                    ->label('Pagado'),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->numeric()
                    ->formatStateUsing(fn ($record) => '$ ' . number_format($record->total_amount - $record->installments()->where('status', 'paid')->sum('amount'), 0, ',', '.'))
                    ->label('Pendiente'),
                Tables\Columns\TextColumn::make('installments')
                    ->label('Cuotas Totales'),
                Tables\Columns\TextColumn::make('paid_installments_count')
                    ->counts('paidInstallments')
                    ->label('Cuotas Pagadas'),
                Tables\Columns\TextColumn::make('next_payment_date')
                    ->date()
                    ->label('Próximo Pago'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'completed' => 'Completado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCredits::route('/'),
            'view' => Pages\ViewCredit::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Ventas';
    }
}
