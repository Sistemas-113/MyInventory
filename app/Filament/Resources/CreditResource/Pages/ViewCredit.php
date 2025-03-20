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
use Filament\Forms;
use App\Models\Payment;
use Filament\Notifications\Notification;

class ViewCredit extends ViewRecord
{
    protected static string $resource = CreditResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CreditStatsOverview::make([
                'record' => $this->record
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
                        ->options(fn ($record) => $record->installments()
                            ->where('status', 'pending')
                            ->get()
                            ->mapWithKeys(fn ($installment) => [
                                $installment->id => "Cuota {$installment->installment_number} - $ " . 
                                number_format($installment->amount, 0, ',', '.')
                            ]))
                        ->required(),
                    Forms\Components\TextInput::make('amount')
                        ->label('Monto')
                        ->numeric()
                        ->required()
                        ->prefix('$'),
                    Forms\Components\Select::make('payment_method')
                        ->label('Método de Pago')
                        ->options([
                            'cash' => 'Efectivo',
                            'transfer' => 'Transferencia',
                            'card' => 'Tarjeta',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->rows(2),
                ])
                ->action(function (array $data, $record): void {
                    $installment = $record->installments()->find($data['installment_id']);
                    
                    // Redondear el monto pagado y el monto de la cuota
                    $amountPaid = round(floatval(preg_replace('/[^0-9.]/', '', $data['amount'])));
                    $installmentAmount = round(floatval($installment->amount));

                    // Crear el registro de pago con monto redondeado
                    $payment = Payment::create([
                        'sale_id' => $record->id,
                        'installment_id' => $installment->id,
                        'amount' => $amountPaid,
                        'payment_method' => $data['payment_method'],
                        'reference_number' => $data['reference_number'] ?? null,
                        'notes' => $data['notes']
                    ]);

                    // Si el pago es igual o mayor al monto de la cuota, marcarla como pagada
                    if ($amountPaid >= $installmentAmount) {
                        $installment->update([
                            'status' => 'paid',
                            'paid_date' => now()
                        ]);

                        $message = sprintf(
                            'Pago de $ %s registrado. Cuota marcada como pagada.',
                            number_format($amountPaid, 0, ',', '.')
                        );
                    } else {
                        $message = sprintf(
                            'Pago parcial de $ %s registrado.',
                            number_format($amountPaid, 0, ',', '.')
                        );
                    }

                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

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
                
            Actions\EditAction::make()
                ->label('Editar Crédito')
                ->icon('heroicon-m-pencil-square'),
                
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
                        ->grow(),
                ])->from('md'),

                Tabs::make('Detalles del Crédito')
                    ->tabs([
                        Tabs\Tab::make('Cuotas')
                            ->icon('heroicon-m-currency-dollar')
                            ->badge($this->record->remaining_installments)
                            ->schema([
                                TextEntry::make('installments')
                                    ->label(false)
                                    ->state(function ($record) {
                                        $installments = $record->installments()->get();
                                        
                                        return view('livewire.tables.installments-table', [
                                            'installments' => $installments
                                        ]);
                                    })
                                    ->columnSpanFull(),

                            ]),
                            
                        Tabs\Tab::make('Productos')
                            ->icon('heroicon-m-shopping-cart')
                            ->badge(fn ($record) => $record->details->count())
                            ->schema([
                                TextEntry::make('details')
                                    ->label(false)
                                    ->state(function ($record) {
                                        $html = '<div class="px-4 py-2">
                                                    <div class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-6">';
                                        
                                        foreach ($record->details as $detail) {
                                            $html .= "
                                                <div class='bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-lg border border-gray-200 dark:border-gray-700'>
                                                    <div class='p-6'>
                                                        <div class='flex justify-between items-start'>
                                                            <div class='space-y-1'>
                                                                <h3 class='text-lg font-semibold text-gray-900 dark:text-white'>{$detail->product_name}</h3>
                                                                <p class='text-sm text-gray-500 dark:text-gray-400'>{$detail->identifier_type}: {$detail->identifier}</p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class='mt-4 space-y-3'>
                                                            <div class='grid grid-cols-2 gap-4'>
                                                                <div class='flex flex-col'>
                                                                    <span class='text-sm text-gray-500 dark:text-gray-400'>Cantidad</span>
                                                                    <span class='font-medium text-gray-900 dark:text-white'>{$detail->quantity} unidades</span>
                                                                </div>
                                                                <div class='flex flex-col'>
                                                                    <span class='text-sm text-gray-500 dark:text-gray-400'>Precio Unitario</span>
                                                                    <span class='font-medium text-gray-900 dark:text-white'>$ " . number_format($detail->unit_price, 0, ',', '.') . "</span>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class='pt-3 border-t border-gray-200 dark:border-gray-700'>
                                                                <div class='flex justify-between items-center'>
                                                                    <span class='text-sm font-medium text-gray-500 dark:text-gray-400'>Subtotal</span>
                                                                    <span class='text-lg font-bold text-primary-600 dark:text-primary-400'>
                                                                        $ " . number_format($detail->subtotal, 0, ',', '.') . "
                                                                    </span>
                                                                </div>
                                                            </div>";
                                            
                                            if ($detail->product_description) {
                                                $html .= "
                                                    <div class='mt-3 pt-3 border-t border-gray-200 dark:border-gray-700'>
                                                        <p class='text-sm text-gray-600 dark:text-gray-300'>{$detail->product_description}</p>
                                                    </div>";
                                            }
                                            
                                            $html .= "
                                                    </div>
                                                </div>
                                            </div>";
                                        }
                                        
                                        $html .= '</div></div>';
                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                                    ->columnSpanFull()
                                    ->html(),
                            ]),

                        Tabs\Tab::make('Historial de Pagos')
                            ->icon('heroicon-m-clock')
                            ->badge(fn ($record) => $record->payments()->count())
                            ->schema([
                                TextEntry::make('payments')
                                    ->label(false)
                                    ->state(function ($record) {
                                        return view('livewire.tables.payments-table', [
                                            'payments' => $record->payments()->latest()->get()
                                        ]);
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->activeTab(fn () => 0),
            ]);
    }
}
