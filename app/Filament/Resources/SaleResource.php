<?php

namespace App\Filament\Resources;

use App\Models\Sale;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\SaleResource\Pages;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Ventas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Información de Venta')
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->relationship('client', 'name')
                        ->required()
                        ->label('Cliente'),
                    Forms\Components\Select::make('payment_type')
                        ->options([
                            'cash' => 'Efectivo',
                            'credit' => 'Crédito',
                            'card' => 'Tarjeta'
                        ])
                        ->live()
                        ->required()
                        ->label('Tipo de Pago'),
                ])->columns(2),

            Section::make('Productos')
                ->schema([
                    Forms\Components\Repeater::make('details')
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->relationship('product', 'name')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if ($state) {
                                        $product = \App\Models\Product::find($state);
                                        if ($product) {
                                            $set('unit_price', $product->price);
                                            $quantity = $get('quantity') ?? 1;
                                            $set('subtotal', $product->price * $quantity);
                                        }
                                    }
                                }),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->live(),
                            Forms\Components\TextInput::make('unit_price')
                                ->numeric()
                                ->required()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('subtotal')
                                ->numeric()
                                ->dehydrated(),
                        ])
                        ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                            // Calcular subtotal de productos
                            $subtotal = collect($state ?? [])->sum(function ($detail) {
                                return floatval($detail['quantity'] ?? 0) * floatval($detail['unit_price'] ?? 0);
                            });

                            // Aplicar interés si es venta a crédito
                            if ($get('payment_type') === 'credit' && $get('interest_rate')) {
                                $interest = ($subtotal * $get('interest_rate')) / 100;
                                $total = $subtotal + $interest;
                            } else {
                                $total = $subtotal;
                            }

                            $set('total_amount', $total);
                        })
                        ->columns(4)
                        ->required()
                        ->minItems(1)
                        ->defaultItems(1)
                        ->relationship('details'),

                    Forms\Components\TextInput::make('total_amount')
                        ->label('Total')
                        ->prefix('$')
                        ->disabled()
                        ->required()
                        ->numeric()
                        ->dehydrated(true) // Importante: asegurar que se envíe al servidor
                ]),

            Section::make('Información de Crédito')
                ->schema([
                    Forms\Components\TextInput::make('interest_rate')
                        ->numeric()
                        ->label('Tasa de Interés (%)')
                        ->visible(fn (Forms\Get $get) => $get('payment_type') === 'credit')
                        ->required(fn (Forms\Get $get) => $get('payment_type') === 'credit')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $subtotal = collect($get('details') ?? [])->sum(function ($detail) {
                                return floatval($detail['quantity'] ?? 0) * floatval($detail['unit_price'] ?? 0);
                            });
                            
                            if ($state && $subtotal > 0) {
                                $interest = ($subtotal * $state) / 100;
                                $total = $subtotal + $interest;
                                $set('total_amount', number_format($total, 2, '.', ''));
                            }
                        }),
                    Forms\Components\TextInput::make('installments')
                        ->numeric()
                        ->label('Número de Cuotas')
                        ->visible(fn (Forms\Get $get) => $get('payment_type') === 'credit')
                        ->required(fn (Forms\Get $get) => $get('payment_type') === 'credit'),
                    Forms\Components\DatePicker::make('first_payment_date')
                        ->label('Fecha Primer Pago')
                        ->visible(fn (Forms\Get $get) => $get('payment_type') === 'credit')
                        ->required(fn (Forms\Get $get) => $get('payment_type') === 'credit'),
                ])->columns(3)
                ->visible(fn (Forms\Get $get) => $get('payment_type') === 'credit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('client.name')
                ->sortable()
                ->label('Cliente'),
            Tables\Columns\TextColumn::make('total_amount')
                ->money('usd')
                ->sortable()
                ->label('Total'),
            Tables\Columns\TextColumn::make('payment_type')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'cash' => 'Efectivo',
                    'credit' => 'Crédito',
                    'card' => 'Tarjeta',
                })
                ->color(fn (string $state): string => match ($state) {
                    'cash' => 'success',
                    'credit' => 'warning',
                    'card' => 'primary',
                }),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'pending' => 'Pendiente',
                    'completed' => 'Completada',
                    'cancelled' => 'Cancelada',
                })
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                }),
            Tables\Columns\TextColumn::make('interest_rate')
                ->label('Tasa Interés')
                ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '-'),
            Tables\Columns\TextColumn::make('installments')
                ->label('Cuotas'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
