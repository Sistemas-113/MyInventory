<?php

namespace App\Services;

use App\Models\{Sale, Product};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Log;

class SaleService 
{
    public function createSale(array $data): Sale
    {
        try {
            // Validar datos básicos y detalles antes de proceder
            $this->validateBasicData($data);
            $this->validateDetails($data);

            return DB::transaction(function () use ($data) {
                // Calcular los totales finales
                $totals = $this->calculateTotals($data);
                
                // Crear la venta
                $sale = $this->createSaleRecord($data, $totals);
                
                // Crear los detalles
                $this->createSaleDetails($sale, $data['details']);
                
                // Si es crédito, crear las cuotas
                if ($this->isCreditSale($sale)) {
                    $this->createCreditInstallments($sale, $totals['finalTotal']);
                }

                return $sale;
            });
        } catch (\Exception $e) {
            Log::error('Error en createSale', ['message' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    protected function calculateTotals(array $data): array
    {
        $subtotal = $this->calculateSubTotal($data['details']);
        $initialPayment = floatval($data['initial_payment']);
        $remaining = max(0, $subtotal - $initialPayment);
        
        $finalTotal = $remaining;
        if ($data['payment_type'] === 'credit' && !empty($data['interest_rate'])) {
            $interest = ($remaining * floatval($data['interest_rate'])) / 100;
            $finalTotal += $interest;
        }

        return [
            'subtotal' => $subtotal,
            'remaining' => $remaining,
            'finalTotal' => $finalTotal
        ];
    }

    protected function createSaleRecord(array $data, array $totals): Sale
    {
        return Sale::create([
            'client_id' => $data['client_id'],
            'payment_type' => $data['payment_type'],
            'total_amount' => $totals['finalTotal'],
            'status' => 'pending',
            'interest_rate' => $data['interest_rate'] ?? null,
            'installments' => $data['installments'] ?? null,
            'first_payment_date' => $data['first_payment_date'] ?? null,
            'initial_payment' => $data['initial_payment'] ?? 0,
        ]);
    }

    protected function createSaleDetails(Sale $sale, array $details): void
    {
        try {
            foreach ($details as $detail) {
                // Validar datos mínimos requeridos incluyendo purchase_price
                if (!isset($detail['product_name'], $detail['quantity'], 
                    $detail['unit_price'], $detail['purchase_price'])) {
                    throw new Halt('Datos de producto incompletos');
                }

                // Limpiar y convertir valores numéricos
                $quantity = intval($detail['quantity']);
                $unitPrice = floatval(preg_replace('/[^0-9.]/', '', $detail['unit_price']));
                $purchasePrice = floatval(preg_replace('/[^0-9.]/', '', $detail['purchase_price']));
                
                $sale->details()->create([
                    'provider_id' => $detail['provider_id'] ?? null,
                    'product_name' => $detail['product_name'],
                    'product_description' => $detail['product_description'] ?? null,
                    'identifier_type' => $detail['identifier_type'] ?? null,
                    'identifier' => $detail['identifier'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'purchase_price' => $purchasePrice,
                    'subtotal' => $quantity * $unitPrice,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creando detalles de venta', [
                'error' => $e->getMessage(),
                'sale_id' => $sale->id,
                'details' => $details
            ]);
            throw new Halt('Error al crear los detalles de la venta: ' . $e->getMessage());
        }
    }

    protected function isCreditSale(Sale $sale): bool
    {
        return $sale->payment_type === 'credit' && $sale->installments > 0;
    }

    private function validateBasicData(array $data): void
    {
        if (!isset($data['client_id'], $data['payment_type'])) {
            throw new Halt('Datos básicos de venta incompletos');
        }

        if ($data['payment_type'] === 'credit') {
            if (!isset($data['installments'], $data['first_payment_date'])) {
                throw new Halt('Datos de crédito incompletos');
            }
            if ($data['installments'] < 1) {
                throw new Halt('El número de cuotas debe ser mayor a 0');
            }
        }
    }

    private function validateDetails(array $data): void
    {
        if (!isset($data['details']) || empty($data['details'])) {
            throw new Halt('La venta debe tener al menos un producto');
        }

        foreach ($data['details'] as $detail) {
            if (!isset($detail['quantity'], $detail['unit_price'], 
                      $detail['product_name'], $detail['identifier'])) {
                throw new Halt('Detalle de producto incompleto');
            }
            if ($detail['quantity'] < 1) {
                throw new Halt('La cantidad debe ser mayor a 0');
            }
            if ($detail['unit_price'] <= 0) {
                throw new Halt('El precio debe ser mayor a 0');
            }
        }
    }

    private function calculateSubTotal(array $details): float
    {
        return collect($details)->sum(function ($detail) {
            return (float)$detail['quantity'] * (float)$detail['unit_price'];
        });
    }

    private function calculateFinalTotal(float $subTotal, array $data): float
    {
        $total = $subTotal;
        
        // Restar cuota inicial
        if (isset($data['initial_payment']) && $data['initial_payment'] > 0) {
            $total = max(0, $total - (float)$data['initial_payment']);
        }
        
        // Aplicar interés si es crédito
        if ($data['payment_type'] === 'credit' && isset($data['interest_rate']) && $data['interest_rate'] > 0) {
            $interest = ($total * (float)$data['interest_rate']) / 100;
            $total += $interest;
        }
        
        return $total;
    }

    private function createCreditInstallments(Sale $sale, float $finalTotal): void
    {
        $installmentAmount = round($finalTotal / $sale->installments, 2);
        $remainingAmount = $finalTotal;
        $date = Carbon::parse($sale->first_payment_date);

        for ($i = 1; $i <= $sale->installments; $i++) {
            $amount = ($i == $sale->installments) 
                ? $remainingAmount 
                : $installmentAmount;

            $sale->installments()->create([
                'installment_number' => $i,
                'amount' => $amount,
                'due_date' => $date->copy(),
                'status' => 'pending'
            ]);

            $remainingAmount -= $installmentAmount;
            $date->addMonth();
        }
    }
}
