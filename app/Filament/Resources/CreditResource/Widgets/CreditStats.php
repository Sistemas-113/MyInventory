<?php

namespace App\Filament\Resources\CreditResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Sale;
use Livewire\Attributes\On;

class CreditStats extends BaseWidget
{
    public ?Sale $record = null;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $paidAmount = $this->record->installments()
            ->where('status', 'paid')
            ->sum('amount');
            
        $remainingAmount = $this->record->total_amount - $paidAmount;

        return [
            Stat::make('Total Venta', '$' . number_format($this->record->total_amount, 2))
                ->description('Monto total de la venta')
                ->color('success'),
            
            Stat::make('Pagado', '$' . number_format($paidAmount, 2))
                ->description('Total pagado hasta la fecha')
                ->color('success'),
            
            Stat::make('Pendiente', '$' . number_format($remainingAmount, 2))
                ->description('Monto pendiente por pagar')
                ->color('warning'),
            
            Stat::make('Cuotas', $this->record->paidInstallments()->count() . ' / ' . $this->record->installments)
                ->description('Cuotas pagadas vs total')
                ->color('info'),
        ];
    }

    #[On('paymentRegistered')] 
    public function refresh(): void
    {
        $this->getStats();
    }

    #[On('creditUpdated')]
    public function updateStats(): void
    {
        $this->dispatch('refresh');
    }
}
