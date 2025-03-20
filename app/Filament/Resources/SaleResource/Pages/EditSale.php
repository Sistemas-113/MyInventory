<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar los detalles de la venta
        $sale = $this->getRecord();
        $data['details'] = $sale->details()
            ->get()
            ->map(function ($detail) {
                return [
                    'provider_id' => $detail->provider_id,
                    'product_name' => $detail->product_name,
                    'product_description' => $detail->product_description,
                    'identifier_type' => $detail->identifier_type,
                    'identifier' => $detail->identifier,
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->unit_price,
                    'subtotal' => $detail->subtotal,
                ];
            })
            ->toArray();

        return $data;
    }

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $sale = $this->record;
        
        // Si cambió el número de cuotas, regenerar las cuotas pendientes
        if ($sale->wasChanged('installments')) {
            // Eliminar cuotas pendientes
            $sale->installments()->where('status', 'pending')->delete();
            
            // Calcular monto restante (total - cuotas pagadas)
            $paidAmount = $sale->installments()->where('status', 'paid')->sum('amount');
            $remainingAmount = $sale->total_amount - $paidAmount;
            
            // Calcular número de cuotas pendientes
            $remainingInstallments = $sale->installments - $sale->installments()->where('status', 'paid')->count();
            
            if ($remainingInstallments > 0) {
                // Calcular monto por cuota redondeado
                $installmentAmount = ceil($remainingAmount / $remainingInstallments);
                
                // Ajustar la última cuota para que cuadre el total
                $lastInstallmentAmount = $remainingAmount - ($installmentAmount * ($remainingInstallments - 1));
                
                $date = $sale->first_payment_date;
                if (!$date) {
                    $date = now();
                }
                
                $lastPaidInstallment = $sale->installments()->where('status', 'paid')->latest('installment_number')->first();
                $nextInstallmentNumber = $lastPaidInstallment ? $lastPaidInstallment->installment_number + 1 : 1;
                
                // Crear nuevas cuotas
                for ($i = 0; $i < $remainingInstallments; $i++) {
                    $amount = ($i == $remainingInstallments - 1) ? $lastInstallmentAmount : $installmentAmount;
                    
                    $sale->installments()->create([
                        'installment_number' => $nextInstallmentNumber + $i,
                        'amount' => $amount,
                        'due_date' => $date->copy()->addMonths($i),
                        'status' => 'pending'
                    ]);
                }
            }
        }
    }
}
