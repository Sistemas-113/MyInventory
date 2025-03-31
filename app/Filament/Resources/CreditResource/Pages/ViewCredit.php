<?php

namespace App\Filament\Resources\CreditResource\Pages;

use App\Filament\Resources\CreditResource;
use App\Filament\Resources\CreditResource\Widgets\CreditStatsOverview;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\IconPosition;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use App\Models\Payment;
use App\Models\Installment;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;

class ViewCredit extends ViewRecord
{
    protected static string $resource = CreditResource::class;

    protected $listeners = ['openEditPaymentModal'];

    #[On('editPayment')] 
    public function editPayment($installmentId)
    {
        $installment = Installment::with('payments')->find($installmentId);
        $payment = $installment?->payments()->latest()->first();

        if (!$payment) {
            Notification::make()
                ->title('No se encontró el pago')
                ->danger()
                ->send();
            return;
        }

        // Formatear el monto sin separadores de miles para el formulario
        $amount = number_format($payment->amount, 0, '', '');

        $this->mountAction('editPayment', [
            'payment_id' => $payment->id,
            'amount' => $amount,
            'payment_method' => $payment->payment_method,
            'reference_number' => $payment->reference_number,
            'notes' => ''  // Dejamos las notas vacías para que el usuario explique el motivo del cambio
        ]);
    }

    public function openEditPaymentModal($installmentId)
    {
        $installment = Installment::find($installmentId);
        $payment = $installment->payments()->latest()->first();

        if (!$payment) return;

        $this->mountAction('editPayment', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CreditStatsOverview::make([
                'record' => $this->record,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('registerPayment')
                ->label('Registrar Pago')
                ->color('success')
                ->icon('heroicon-m-banknotes')
                ->form([
                    Forms\Components\Select::make('installment_id')
                        ->label('Cuota')
                        ->options(function ($record) {
                            return $record->installments()
                                ->where('status', '!=', 'paid')
                                ->get()
                                ->mapWithKeys(function ($installment) {
                                    $label = "Cuota {$installment->installment_number}";
                                    $remaining = $installment->remaining_amount;
                                    $label .= " - Pendiente: $ " . number_format($remaining, 0, ',', '.');
                                    
                                    if ($installment->total_paid > 0) {
                                        $label .= " (Abonado: $ " . number_format($installment->total_paid, 0, ',', '.') . ")";
                                    }
                                    
                                    return [$installment->id => $label];
                                });
                        })
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            if ($state) {
                                $installment = Installment::find($state);
                                if ($installment) {
                                    $remaining = $installment->remaining_amount;
                                    $set('amount', $remaining);
                                }
                            }
                        }),
                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Fecha de Pago')
                        ->default(now())
                        ->format('Y-m-d')
                        ->required(),
                    Forms\Components\TextInput::make('amount')
                        ->label('Monto a Pagar')
                        ->helperText(function (Forms\Get $get, $record) {
                            if (!$get('installment_id')) return '';
                            
                            $installment = Installment::find($get('installment_id'));
                            $totalDebt = $record->total_amount - $record->payments()->sum('amount');
                            
                            return "Deuda total: $ " . number_format($totalDebt, 0, ',', '.');
                        })
                        ->numeric()
                        ->required()
                        ->prefix('$')
                        ->rules([
                            'required',
                            'numeric',
                            'min:1',
                            function () {
                                return function ($attribute, $value, $fail) {
                                    $sale = $this->getRecord();
                                    $totalDebt = $sale->total_amount - $sale->payments()->sum('amount');
                                    
                                    if ($value > $totalDebt) {
                                        $fail("El monto no puede ser mayor a la deuda total ($ " . 
                                              number_format($totalDebt, 0, ',', '.') . ")");
                                    }
                                };
                            },
                        ]),
                    Forms\Components\Select::make('payment_method')
                        ->label('Método de Pago')
                        ->options([
                            'cash' => 'Efectivo',
                            'transfer' => 'Transferencia',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, $record): void {
                    try {
                        DB::beginTransaction();

                        $amountPaid = round(floatval(preg_replace('/[^0-9.]/', '', $data['amount'])));
                        $paymentDate = \Carbon\Carbon::parse($data['payment_date'])->startOfDay();
                        
                        // Distribuir el pago entre las cuotas
                        $record->distributePayment(
                            amount: $amountPaid,
                            currentInstallmentId: $data['installment_id'],
                            paymentMethod: $data['payment_method'],
                            paymentDate: $paymentDate
                        );

                        DB::commit();

                        Notification::make()
                            ->title('Pago registrado correctamente')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Error al registrar el pago')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('editPaymentByInstallment')
                ->label('Editar Pago')
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('payment_id')
                        ->label('Seleccionar Pago')
                        ->options(function ($record) {
                            return $record->payments()
                                ->latest('payment_date')  // Ordenar por payment_date en lugar de created_at
                                ->get()
                                ->mapWithKeys(function ($payment) {
                                    $installment = $payment->installment;
                                    return [
                                        $payment->id => "Cuota {$installment->installment_number} - $ " . 
                                            number_format($payment->amount, 0, ',', '.') . 
                                            " (" . match($payment->payment_method) {
                                                'cash' => 'Efectivo',
                                                'transfer' => 'Transferencia',
                                                'payment' => 'Pago',
                                                default => ucfirst($payment->payment_method)
                                            } . ") - " . 
                                            $payment->payment_date->format('d/m/Y')
                                    ];
                                });
                        })
                       
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $payment = Payment::find($state);
                                if ($payment) {
                                    $set('amount', number_format($payment->amount, 0, '', ''));
                                    $set('payment_method', $payment->payment_method);
                                    $set('reference_number', $payment->reference_number);
                                    $set('payment_date', $payment->payment_date->format('Y-m-d'));
                                }
                            }
                        }),
                    TextInput::make('amount')
                        ->label('Monto')
                        ->required()
                        ->numeric()
                        ->prefix('$'),
                    Select::make('payment_method')
                        ->label('Método de Pago')
                        ->options([
                            'cash' => 'Efectivo',
                            'transfer' => 'Transferencia',
                            'payment' => 'Pago',
                        ])
                        ->required(),
                    TextInput::make('reference_number')
                        ->label('Número de Referencia')
                        ->nullable()
                        ->visible(fn (Forms\Get $get) => $get('payment_method') === 'transfer'),
                    DatePicker::make('payment_date')
                        ->label('Fecha de Pago')
                        ->required()
                        ->format('Y-m-d'),
                ])
                ->action(function (array $data, $record): void {
                    try {
                        DB::beginTransaction();
                        
                        $payment = Payment::find($data['payment_id']);
                        if (!$payment) {
                            throw new \Exception('No se encontró el pago');
                        }

                        $newAmount = floatval(preg_replace('/[^0-9.]/', '', $data['amount']));
                        $oldAmount = $payment->amount;
                        $paymentDate = \Carbon\Carbon::parse($data['payment_date'])->startOfDay();

                        // Eliminar el pago anterior
                        $payment->delete();

                        // Distribuir el nuevo pago
                        $record->distributePayment(
                            amount: $newAmount,
                            currentInstallmentId: $payment->installment_id,
                            paymentMethod: $data['payment_method'],
                            paymentDate: $paymentDate
                        );

                        DB::commit();

                        Notification::make()
                            ->title('Pago actualizado correctamente')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->title('Error al actualizar el pago')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalWidth('lg'),
                Actions\Action::make('printReceipt')
                ->label('Imprimir Recibo')
                ->icon('heroicon-m-printer')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('¿Desea imprimir el recibo?')
                ->modalDescription('Se generará un PDF con el recibo del crédito.')
                ->modalSubmitActionLabel('Imprimir')
                ->url(fn ($record) => route('credits.print-receipt', $record))
                ->openUrlInNewTab(),
                
            

                Actions\DeleteAction::make()
                ->label('Eliminar Crédito')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                    Section::make()
                        ->schema([
                            TextEntry::make('client.name')
                                ->label('Cliente')
                                ->weight('bold')
                                ->size(TextEntry\TextEntrySize::Large),
                            Grid::make(3)
                                ->schema([
                                    TextEntry::make('total_amount')
                                        ->label('Monto Total')
                                        ->color('danger')
                                        ->formatStateUsing(fn ($state) => '$ ' . number_format($state, 0, ',', '.')),
                                    TextEntry::make('interest_rate')
                                        ->label('Tasa de Interés')
                                        ->color('warning')
                                        ->formatStateUsing(fn ($state) => "{$state}%"),
                                    TextEntry::make('initial_payment')
                                        ->label('Cuota Inicial')
                                        ->color('success')
                                        ->formatStateUsing(fn ($state) => '$ ' . number_format($state, 0, ',', '.')),
                                ]),
                        ])
                        ->grow()
                        ->columnSpan(['lg' => 3]),
                ])->from('lg'),

                Tabs::make('Detalles del Crédito')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Cuotas')
                            ->icon('heroicon-m-currency-dollar')
                            ->badge($this->record->remaining_installments)
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                TextEntry::make('installments')
                                                    ->label(false)
                                                    ->columnSpanFull()
                                                    ->view('filament.resources.credit-resource.components.installments-grid', [
                                                        'installments' => $this->record->installments()
                                                            ->orderBy('installment_number')
                                                            ->get()
                                                    ])
                                            ])
                                            ->columns(1)
                                    ])
                                    ->collapsible(false)
                                    ->columnSpanFull(),
                            ]),
                            
                        Tabs\Tab::make('Productos')
                            ->icon('heroicon-m-shopping-cart')
                            ->badge(fn ($record) => $record->details->count())
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextEntry::make('details')
                                            ->label(false)
                                            ->columnSpanFull()
                                            ->view('filament.resources.credit-resource.components.products-grid', [
                                                'details' => fn ($record) => $record->details()->get()
                                            ])
                                    ])
                                    ->collapsible(false),
                            ]),

                        Tabs\Tab::make('Historial de Pagos')
                            ->icon('heroicon-m-clock')
                            ->badge(fn ($record) => $record->payments()->count())
                            ->schema([
                                RepeatableEntry::make('payments')
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                TextEntry::make('payment_date')
                                                    ->label('Fecha')
                                                    ->date('d/m/Y')
                                                    ->icon('heroicon-m-calendar')
                                                    ->iconPosition(IconPosition::Before),
                                                
                                                TextEntry::make('amount')
                                                    ->label('Monto')
                                                    ->icon('heroicon-m-banknotes')
                                                    ->iconPosition(IconPosition::Before)
                                                    ->money('COP')
                                                    ->color('success'),
                                                    
                                                TextEntry::make('payment_method')
                                                    ->label('Método')
                                                    ->icon('heroicon-m-credit-card')
                                                    ->iconPosition(IconPosition::Before)
                                                    ->badge()
                                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                                        'cash' => 'Efectivo',
                                                        'transfer' => 'Transferencia',
                                                        'payment' => 'Pago',
                                                        'card' => 'Tarjeta',
                                                        default => ucfirst($state),
                                                    })
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'cash' => 'success',
                                                        'transfer' => 'info',
                                                        'payment' => 'primary',
                                                        'card' => 'warning',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('notes')
                                                    ->label('Notas')
                                                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                                                    ->iconPosition(IconPosition::Before)
                                                    ->placeholder('Sin notas'),
                                            ])
                                            ->columns(4),
                                    ])
                                    ->columns(1)
                                    ->contained(false)
                                    ->state(function ($record) {
                                        return $record->payments()
                                            ->with(['installment'])
                                            ->latest()
                                            ->get();
                                    }),
                            ]),
                    ])
                    ->activeTab(fn () => 0),
            ])
            ->columns(3);
    }
}
