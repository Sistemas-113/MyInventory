<?php

namespace App\Filament\Resources\CreditResource\Pages;

use App\Filament\Resources\CreditResource;
use App\Filament\Resources\CreditResource\Widgets\CreditStats;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCredit extends ViewRecord
{
    protected static string $resource = CreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('registerPayment')
                ->label('Registrar Pago')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->form([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Select::make('installment_id')
                                ->label('Cuota a Pagar')
                                ->required()
                                ->options(fn () => $this->record->installments()
                                    ->where('status', 'pending')
                                    ->orderBy('installment_number')
                                    ->get()
                                    ->mapWithKeys(fn ($installment) => [
                                        $installment->id => "Cuota {$installment->installment_number} - \${$installment->amount}"
                                    ]))
                                ->live()
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                    $set('amount', $this->record->installments()->find($state)?->amount ?? 0)),

                            Forms\Components\TextInput::make('amount')
                                ->label('Monto')
                                ->required()
                                ->numeric()
                                ->disabled()
                                ->prefix('$'),

                            Forms\Components\DatePicker::make('paid_date')
                                ->label('Fecha de Pago')
                                ->required()
                                ->default(now())
                                ->maxDate(now()),
                        ])
                        ->columns(1),
                ])
                ->action(function (array $data): void {
                    try {
                        DB::beginTransaction();

                        $installment = $this->record->installments()->find($data['installment_id']);
                        
                        if (!$installment || $installment->status === 'paid') {
                            throw new Halt('Esta cuota ya ha sido pagada.');
                        }

                        // Actualizar la cuota
                        $installment->update([
                            'status' => 'paid',
                            'paid_date' => $data['paid_date'],
                        ]);

                        // Actualizar el estado de la venta si todas las cuotas están pagadas
                        if (!$this->record->installments()->where('status', 'pending')->exists()) {
                            $this->record->update(['status' => 'completed']);
                        }

                        DB::commit();

                        Notification::make()
                            ->title('Pago registrado exitosamente')
                            ->success()
                            ->send();

                        $this->dispatch('creditUpdated');
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Error al registrar el pago')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn() => $this->record->installments()->where('status', 'pending')->exists()),

            Actions\Action::make('viewPayments')
                ->label('Ver Historial')
                ->icon('heroicon-o-clock')
                ->modalHeading('Historial de Pagos')
                ->modalContent(fn () => view('filament.resources.credit-resource.pages.payments-modal', [
                    'payments' => $this->record->installments()
                        ->orderBy('installment_number', 'desc')
                        ->get()
                ]))
                ->modalWidth('5xl')
                ->color('info'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->record->installments()->orderBy('installment_number'))
            ->heading('Historial de Pagos')
            ->description('Registro de todos los pagos realizados')
            ->columns([
                Tables\Columns\TextColumn::make('installment_number')
                    ->label('N° Cuota')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('usd')
                    ->label('Monto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->label('Fecha Vencimiento')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_date')
                    ->date()
                    ->label('Fecha Pago'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Pagado',
                        'pending' => 'Pendiente',
                    }),
            ])
            ->defaultSort('installment_number', 'asc')
            ->striped();
    }

    public function contentTable(Table $table): Table
    {
        return $table
            ->query($this->record->installments()->orderBy('installment_number'))
            ->heading('Historial de Pagos')
            ->description('Registro detallado de todos los pagos')
            ->columns([
                Tables\Columns\TextColumn::make('installment_number')
                    ->label('N° Cuota')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('usd')
                    ->label('Monto')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->label('Fecha Vencimiento')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_date')
                    ->date()
                    ->label('Fecha Pago'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'pending',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'paid' => 'Pagado',
                        'pending' => 'Pendiente',
                    }),
            ])
            ->defaultSort('installment_number', 'asc')
            ->striped()
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid' => 'Pagado',
                        'pending' => 'Pendiente',
                    ])
                    ->label('Estado'),
            ])
            ->filtersFormColumns(2);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CreditStats::class => CreditStats::make([
                'record' => $this->getRecord(),
            ]),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        return [
            'payments' => $this->record->installments()
                ->orderBy('installment_number')
                ->get(),
        ];
    }

    protected function getViewContentFooter(): ?View
    {
        return view('filament.resources.credit-resource.pages.payments-table', [
            'payments' => $this->record->installments()
                ->orderBy('installment_number')
                ->get()
        ]);
    }

    protected function getViewContent(): ?View
    {
        return view('filament.resources.credit-resource.pages.view-credit', [
            'record' => $this->record,
        ]);
    }
}
