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
            Log::debug('Iniciando createSale con datos:', ['data' => $data]);

            return DB::transaction(function () use ($data) {
                // Crear venta base
                $sale = Sale::create([
                    'client_id' => $data['client_id'],
                    'payment_type' => $data['payment_type'],
                    'total_amount' => $data['total_amount'],
                    'status' => 'pending',
                    'interest_rate' => $data['interest_rate'] ?? null,
                    'installments' => $data['installments'] ?? null,
                    'first_payment_date' => $data['first_payment_date'] ?? null,
                ]);

                Log::info('Venta base creada', [
                    'sale_id' => $sale->id,
                    'total' => $sale->total_amount,
                    'installments' => $sale->installments
                ]);

                // Si es venta a crédito, crear las cuotas
                if ($sale->payment_type === 'credit' && $sale->installments > 0) {
                    $this->createInstallments($sale, $sale->total_amount, [
                        'installments' => $sale->installments,
                        'first_payment_date' => $sale->first_payment_date
                    ]);
                }

                return $sale;
            });
        } catch (\Exception $e) {
            Log::error('Error en createSale', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    private function createBaseSale(array $data): Sale
    {
        try {
            $saleData = [
                'client_id' => $data['client_id'],
                'payment_type' => $data['payment_type'],
                'status' => 'pending',
                'interest_rate' => $data['interest_rate'] ?? null,
                'installments' => $data['installments'] ?? null,
                'first_payment_date' => $data['first_payment_date'] ?? null,
            ];

            Log::info('Creando venta base con datos:', $saleData);
            
            return Sale::create($saleData);
        } catch (\Exception $e) {
            Log::error('Error creando venta base', [
                'error' => $e->getMessage(),
                'data' => $saleData ?? []
            ]);
            throw $e;
        }
    }

    private function processSaleDetails(Sale $sale, array $details): float
    {
        $subtotal = 0;

        foreach ($details as $index => $detail) {
            try {
                Log::debug("Procesando detalle #{$index}", $detail);

                // Validar datos del detalle
                if (!isset($detail['product_id'], $detail['quantity'], $detail['unit_price'])) {
                    Log::error("Detalle inválido", ['detail' => $detail]);
                    throw new \Exception("Detalle de producto incompleto");
                }

                $product = Product::findOrFail($detail['product_id']);
                $this->validateStock($product, $detail['quantity']);

                $detailSubtotal = $this->createSaleDetail($sale, $product, $detail);
                $subtotal += $detailSubtotal;

                Log::info("Detalle #{$index} procesado", [
                    'product' => $product->name,
                    'subtotal' => $detailSubtotal
                ]);

            } catch (\Exception $e) {
                Log::error("Error procesando detalle #{$index}", [
                    'detail' => $detail,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        return $subtotal;
    }

    private function validateStock(Product $product, int $quantity): void
    {
        if (!$product->hasEnoughStock($quantity)) {
            throw new Halt("Stock insuficiente para {$product->name}. Disponible: {$product->stock}");
        }
    }

    private function createSaleDetail(Sale $sale, Product $product, array $detail): float
    {
        $subtotal = $detail['quantity'] * $detail['unit_price'];

        $sale->details()->create([
            'product_id' => $product->id,
            'quantity' => $detail['quantity'],
            'unit_price' => $detail['unit_price'],
            'subtotal' => $subtotal
        ]);

        $product->decrementStock($detail['quantity']);

        return $subtotal;
    }

    private function calculateTotal(float $subtotal, array $data): float
    {
        if ($this->isCreditSale($data) && isset($data['interest_rate']) && $data['interest_rate'] > 0) {
            $interest = ($subtotal * $data['interest_rate']) / 100;
            return $subtotal + $interest;
        }
        return $subtotal;
    }

    private function isCreditSale(array $data): bool
    {
        return ($data['payment_type'] ?? '') === 'credit' &&
            isset($data['installments'], $data['first_payment_date']) &&
            $data['installments'] > 0;
    }

    private function createInstallments(Sale $sale, float $total, array $data): void
    {
        $installmentAmount = round($total / $data['installments'], 2);
        $remainingAmount = $total;
        $date = Carbon::parse($data['first_payment_date']);

        for ($i = 1; $i <= $data['installments']; $i++) {
            $amount = ($i == $data['installments']) ? $remainingAmount : $installmentAmount;
            $this->createInstallment($sale, $i, $amount, $date->copy());
            $remainingAmount -= $installmentAmount;
            $date->addMonth();
        }
    }

    private function createInstallment(Sale $sale, int $number, float $amount, Carbon $date): void
    {
        $sale->installments()->create([
            'installment_number' => $number,
            'amount' => $amount,
            'due_date' => $date,
            'status' => 'pending'
        ]);
    }
}
