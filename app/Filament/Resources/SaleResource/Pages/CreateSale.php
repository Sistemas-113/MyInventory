<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\SaleService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use Carbon\Carbon;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            Log::debug('Datos del formulario antes de crear', ['data' => $data]);
            
            // Validar datos básicos
            $this->validateSaleData($data);

            // Asegurarse que details exista y sea array
            if (!isset($data['details']) || !is_array($data['details'])) {
                $data['details'] = [];
            }

            // Formatear los detalles
            $data['details'] = collect($data['details'])
                ->filter() // Eliminar elementos vacíos
                ->map(function ($detail) {
                    return [
                        'provider_id' => $detail['provider_id'] ?? null,
                        'product_name' => $detail['product_name'] ?? '',
                        'product_description' => $detail['product_description'] ?? null,
                        'identifier_type' => $detail['identifier_type'] ?? 'other',
                        'identifier' => $detail['identifier'] ?? '',
                        'quantity' => intval($detail['quantity'] ?? 1),
                        'unit_price' => floatval(preg_replace('/[^0-9.]/', '', $detail['unit_price'] ?? '0')),
                    ];
                })
                ->toArray();

            Log::debug('Datos procesados', ['processed_data' => $data]);

            return $data;
        } catch (\Exception $e) {
            Log::error('Error procesando datos', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            DB::beginTransaction();
            
            // Calcular subtotal de productos
            $subtotal = collect($data['details'])->sum(function ($detail) {
                return floatval($detail['quantity']) * floatval(preg_replace('/[^0-9.]/', '', $detail['unit_price']));
            });

            // Calcular monto después de cuota inicial
            $initialPayment = floatval($data['initial_payment'] ?? 0);
            $remainingAmount = max(0, $subtotal - $initialPayment);
            
            // Calcular interés si es crédito
            $finalAmount = $remainingAmount;
            if ($data['payment_type'] === 'credit' && isset($data['interest_rate'])) {
                $interest = ($remainingAmount * floatval($data['interest_rate'])) / 100;
                $finalAmount = $remainingAmount + $interest;
            }

            // Asegurar que el total_amount sea numérico
            $totalAmount = floatval($finalAmount);
            
            // Crear la venta
            $sale = parent::handleRecordCreation([
                'client_id' => $data['client_id'],
                'payment_type' => $data['payment_type'],
                'initial_payment' => $initialPayment,
                'interest_rate' => floatval($data['interest_rate'] ?? 0),
                'installments' => intval($data['installments'] ?? 0),
                'first_payment_date' => $data['first_payment_date'] ?? null,
                'total_amount' => $totalAmount,  // Asegurar que sea un valor numérico
                'status' => 'pending',
            ]);

            // Crear los detalles con valores numéricos validados
            foreach ($data['details'] as $detail) {
                $price = floatval(preg_replace('/[^0-9.]/', '', $detail['unit_price']));
                $quantity = intval($detail['quantity']);
                
                $sale->details()->create([
                    'provider_id' => $detail['provider_id'] ?? null,
                    'product_name' => $detail['product_name'],
                    'product_description' => $detail['product_description'] ?? null,
                    'identifier_type' => $detail['identifier_type'],
                    'identifier' => $detail['identifier'],
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'subtotal' => $quantity * $price,
                ]);
            }

            if ($data['payment_type'] === 'credit') {
                $this->createInstallments($sale, $totalAmount);
            }

            DB::commit();
            return $sale;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en handleRecordCreation', [
                'message' => $e->getMessage(),
                'data' => $data,
                'total_amount' => $totalAmount ?? null
            ]);
            throw $e;
        }
    }

    protected function prepareSaleData(array $data): array
    {
        try {
            $details = $data['details'] ?? [];
            $subtotal = $this->calculateSubtotal($details);

            // Formatear los detalles para inserción directa
            $formattedDetails = [];
            foreach ($details as $key => $detail) {
                if (is_array($detail)) {
                    $formattedDetails[] = [
                        'provider_id' => $detail['provider_id'] ?? null,
                        'product_name' => $detail['product_name'],
                        'product_description' => $detail['product_description'] ?? null,
                        'identifier_type' => $detail['identifier_type'],
                        'identifier' => $detail['identifier'],
                        'quantity' => intval($detail['quantity']),
                        'unit_price' => floatval(preg_replace('/[^0-9.]/', '', $detail['unit_price'])),
                        'subtotal' => floatval($detail['quantity']) * floatval(preg_replace('/[^0-9.]/', '', $detail['unit_price'])),
                    ];
                }
            }

            return [
                'client_id' => $data['client_id'],
                'payment_type' => $data['payment_type'],
                'initial_payment' => floatval($data['initial_payment'] ?? 0),
                'interest_rate' => floatval($data['interest_rate'] ?? 0),
                'installments' => intval($data['installments'] ?? 0),
                'first_payment_date' => $data['first_payment_date'] ?? null,
                'total_amount' => $subtotal,
                'status' => 'pending',
                'details' => $formattedDetails,
            ];
        } catch (\Exception $e) {
            Log::error('Error preparando datos de venta', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new Halt('Error preparando los datos de la venta: ' . $e->getMessage());
        }
    }

    protected function calculateSubtotal(array $details): float
    {
        return collect($details)->sum(function ($detail) {
            return intval($detail['quantity']) * floatval(preg_replace('/[^0-9.]/', '', $detail['unit_price']));
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function validateSaleData(array $data): void
    {
        if (!isset($data['client_id'], $data['payment_type'])) {
            throw new Halt('Datos incompletos para la venta.');
        }

        if (!isset($data['details']) || empty($data['details'])) {
            throw new Halt('Debe agregar al menos un producto a la venta.');
        }

        if ($data['payment_type'] === 'credit') {
            $this->validateCreditData($data);
        }
    }

    private function validateCreditData(array $data): void
    {
        if (!isset($data['installments']) || $data['installments'] <= 0) {
            throw new Halt('Para ventas a crédito debe especificar el número de cuotas.');
        }

        if (!isset($data['first_payment_date'])) {
            throw new Halt('Debe especificar la fecha del primer pago.');
        }
    }

    protected function createInstallments(Sale $sale, float $totalAmount): void
    {
        try {
            $numberOfInstallments = $sale->installments;
            // Redondear el monto de la cuota al siguiente número entero
            $installmentAmount = ceil($totalAmount / $numberOfInstallments);
            
            // Calcular la última cuota para que cuadre el total
            $totalForRegularInstallments = $installmentAmount * ($numberOfInstallments - 1);
            $lastInstallmentAmount = $totalAmount - $totalForRegularInstallments;
            
            $date = Carbon::parse($sale->first_payment_date);

            for ($i = 1; $i <= $numberOfInstallments; $i++) {
                // La última cuota ajusta cualquier diferencia por redondeo
                $amount = ($i == $numberOfInstallments) 
                    ? $lastInstallmentAmount 
                    : $installmentAmount;

                $sale->installments()->create([
                    'installment_number' => $i,
                    'amount' => $amount,
                    'due_date' => $date->copy(),
                    'status' => 'pending'
                ]);

                $date->addMonth();
            }

        } catch (\Exception $e) {
            Log::error('Error creando cuotas', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage()
            ]);
            throw new Halt('Error al crear las cuotas: ' . $e->getMessage());
        }
    }
}
