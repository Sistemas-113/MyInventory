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
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use App\Exports\SalesExport;
use Filament\Actions;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';

    public static function getNavigationGroup(): string
    {
        return __('Ventas');
    }

    public static function getModelLabel(): string
    {
        return __('Ventas');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Ventas');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Información de Venta')
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->relationship('client', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Cliente')
                        ->placeholder('Seleccione un cliente'),
                    Forms\Components\Select::make('payment_type')
                        ->options([
                            'cash' => 'Contado',
                            'semi_cash' => 'Semi Contado',
                            'credit' => 'Crédito'
                        ])
                        ->live()
                        ->required()
                        ->label('Tipo de Pago'),
                    Forms\Components\TextInput::make('initial_payment')
                        ->label('Cuota Inicial')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->live(onBlur: true)
                        ->dehydrateStateUsing(fn ($state) => floatval(preg_replace('/[^0-9.]/', '', $state ?? '0')))
                        ->default(0)
                        ->visible(fn (Forms\Get $get) => $get('payment_type') === 'credit')
                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                            $totalAmount = static::calculateTotalAmount($get('details') ?? []);
                            $initialPayment = floatval(preg_replace('/[^0-9.]/', '', $state ?? '0'));
                            $remaining = max(0, $totalAmount - $initialPayment);
                            
                            if ($get('payment_type') === 'credit') {
                                static::calculateCreditAmount($remaining, $get('interest_rate'), $set);
                            } else {
                                $set('total_amount', $remaining);
                            }
                        }),
                ])->columns(2),

            Section::make('Productos')
                ->schema([
                    Forms\Components\Repeater::make('details')
                        ->schema([
                            Forms\Components\Select::make('provider_id')
                                ->options(function () {
                                    return \App\Models\Provider::pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->label('Proveedor'),
                            Forms\Components\TextInput::make('product_name')
                                ->label('Nombre del Producto')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('product_description')
                                ->label('Descripción')
                                ->maxLength(500),
                            Forms\Components\Select::make('identifier_type')
                                ->label('Tipo de Identificador')
                                ->options([
                                    'serial' => 'Serial',
                                    'imei' => 'IMEI',
                                    'code' => 'Código',
                                    'other' => 'Otro'
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('identifier')
                                ->label('Identificador')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $state, Forms\Set $set, Forms\Get $get) {
                                    $quantity = (int)$state;
                                    $price = (int)($get('unit_price') ?? 0);
                                    $subtotal = $quantity * $price;
                                    
                                    $set('subtotal', $subtotal);
                                    
                                    // Recalcular total general
                                    $allDetails = $get('../../details');
                                    if (is_array($allDetails)) {
                                        $total = collect($allDetails)->sum('subtotal');
                                        $set('../../subtotal', $total);
                                    }
                                }),
                            Forms\Components\TextInput::make('purchase_price')
                                ->label('Precio de Compra')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->live(onBlur: true)
                                ->dehydrateStateUsing(fn ($state) => round(floatval(preg_replace('/[^0-9.]/', '', $state ?? '0')), 2))
                                ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    if ($state) {
                                        $set('purchase_price', round(floatval(preg_replace('/[^0-9.]/', '', $state)), 2));
                                    }
                                }),
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Precio Unitario')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->live(onBlur: true)
                                ->dehydrateStateUsing(fn ($state) => floatval(preg_replace('/[^0-9.]/', '', $state ?? '0')))
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    static::updateDetailTotals($state, $get, $set);
                                }),
                            Forms\Components\TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->disabled()
                                ->prefix('$'),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->required()
                        ->minItems(1)
                        ->columnSpanFull()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (empty($state)) {
                                $set('details', [[
                                    'quantity' => 1,
                                    'unit_price' => 0,
                                ]]);
                            }
                        }),
                ]),

            Section::make('Resumen')
                ->schema([
                    Forms\Components\TextInput::make('total_amount')
                        ->label('Total Final')
                        ->disabled()
                        ->prefix('$'),
                ])
                ->columns(1),

            Section::make('Información de Crédito')
                ->schema([
                    Forms\Components\TextInput::make('interest_rate')
                        ->numeric()
                        ->label('Tasa de Interés (%)')
                        ->visible(fn (Forms\Get $get) => $get('payment_type') === 'credit')
                        ->required(fn (Forms\Get $get) => $get('payment_type') === 'credit')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $total = collect($get('details') ?? [])->sum('subtotal') ?? 0;
                            $initialPayment = (float) str_replace(['.', ','], ['', '.'], $get('initial_payment') ?? 0);
                            $remaining = max(0, $total - $initialPayment);
                            
                            if ($state && $remaining > 0) {
                                $interest = ($remaining * (float)$state) / 100;
                                $finalTotal = $remaining + $interest;
                                $set('total_amount', number_format($finalTotal, 0, ',', '.'));
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

    protected static function calculateTotalAmount(array $details): float
    {
        return collect($details)->sum(function ($detail) {
            $quantity = intval($detail['quantity'] ?? 1);
            $price = floatval(preg_replace('/[^0-9.]/', '', $detail['unit_price'] ?? '0'));
            return $quantity * $price;
        });
    }

    protected static function calculateCreditAmount(float $remaining, ?string $interestRate, Forms\Set $set): void
    {
        $rate = floatval($interestRate ?? 0);
        if ($rate > 0 && $remaining > 0) {
            $interest = ($remaining * $rate) / 100;
            $finalTotal = $remaining + $interest;
            $set('total_amount', $finalTotal);
        }
    }

    protected static function updateDetailTotals(string $state, Forms\Get $get, Forms\Set $set): void
    {
        $price = floatval(preg_replace('/[^0-9.]/', '', $state ?? '0'));
        $quantity = intval($get('quantity') ?? 1);
        $subtotal = $price * $quantity;
        
        $set('subtotal', $subtotal);
        
        // Recalcular total general
        $allDetails = $get('../../details');
        if (is_array($allDetails)) {
            $total = static::calculateTotalAmount($allDetails);
            $set('../../total_amount', $total);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->sortable()
                    ->label('Cliente'),
                Tables\Columns\TextColumn::make('details')
                    ->label('Productos')
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        return $record->details->pluck('product_name')->join(', ');
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => '$ ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->label('Total'),
                Tables\Columns\TextColumn::make('payment_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Contado',
                        'credit' => 'Crédito',
                        'card' => 'Tarjeta',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'credit' => 'warning',
                        'card' => 'primary',
                    })
                    ->label('Tipo de Pago'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->toggleable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->label('Estado'),
                Tables\Columns\TextColumn::make('interest_rate')
                    ->label('Tasa Interés')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '-'),
                Tables\Columns\TextColumn::make('installments')
                    ->label('Cuotas')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('client.phone')
                    ->label('Teléfono')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('client.email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        return Excel::download(new SalesExport(), 'ventas.xlsx');
                    })
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_type')
                    ->label('Tipo de Pago')
                    ->options([
                        'cash' => 'Contado',
                        'credit' => 'Crédito',
                        'card' => 'Tarjeta',
                    ])
                    ->indicator('Tipo Pago'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'completed' => 'Completada', 
                        'cancelled' => 'Cancelada',
                    ])
                    ->indicator('Estado'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data): void {
                        $query->when(
                            $data['created_from'],
                            fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                        )->when(
                            $data['created_until'],
                            fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Desde ' . Carbon::parse($data['created_from'])->format('d/m/Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Hasta ' . Carbon::parse($data['created_until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('generateInvoice')
                    ->tooltip('Generar Factura')
                    ->label('Factura')
                    ->icon('heroicon-m-document-text')
                    ->color('info')
                    ->url(fn (Sale $record) => route('sales.generate-invoice', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->tooltip('Editar Venta')
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Eliminar Venta')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Venta')
                    ->modalDescription('¿Está seguro que desea eliminar esta venta? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->action(function (Sale $record) {
                        $record->details()->delete();
                        $record->installments()->delete();
                        $record->payments()->delete();
                        $record->delete();

                        Notification::make()
                            ->title('Venta eliminada')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar Ventas Seleccionadas')
                        ->modalDescription('¿Está seguro que desea eliminar las ventas seleccionadas? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->details()->delete();
                                $record->installments()->delete();
                                $record->payments()->delete();
                                $record->delete();
                            });

                            Notification::make()
                                ->title('Ventas eliminadas')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportMonthly')
                ->label('Reporte Mensual')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    return Excel::download(
                        new SalesExport(now()->startOfMonth(), now()->endOfMonth()),
                        'ventas-' . now()->format('Y-m') . '.xlsx'
                    );
                }),

            Actions\Action::make('exportQuarterly')
                ->label('Reporte Trimestral')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    return Excel::download(
                        new SalesExport(now()->startOfQuarter(), now()->endOfQuarter()),
                        'ventas-trimestre-' . now()->quarter . '-' . now()->year . '.xlsx'
                    );
                }),

            Actions\Action::make('exportYearly')
                ->label('Reporte Anual')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    return Excel::download(
                        new SalesExport(now()->startOfYear(), now()->endOfYear()),
                        'ventas-' . now()->year . '.xlsx'
                    );
                }),

            Actions\Action::make('exportCredit')
                ->label('Exportar Créditos')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->action(function () {
                    return Excel::download(
                        new CreditSalesExport(),
                        'ventas-credito-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),

            Actions\Action::make('exportCash')
                ->label('Exportar Contado')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new CashSalesExport(),
                        'ventas-contado-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            SaleResource\Widgets\SaleStatsOverview::class,
        ];
    }

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make()
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.resources.sales.*'))
                ->label(static::getModelLabel())
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->url(static::getUrl()),
            
            \Filament\Navigation\NavigationItem::make('export-sales')
                ->label('Exportar Ventas')
                ->icon('heroicon-o-document-arrow-down')
                ->url(route('filament.resources.sales.export'))
                ->group(static::getNavigationGroup()),
        ];
    }
}
