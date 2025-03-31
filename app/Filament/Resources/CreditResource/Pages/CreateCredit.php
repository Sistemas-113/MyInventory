<?php

namespace App\Filament\Resources\CreditResource\Pages;

use App\Filament\Resources\CreditResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class CreateCredit extends CreateRecord
{
    protected static string $resource = CreditResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            Log::debug('Datos del formulario antes de crear', ['data' => $data]);

            // Validar datos básicos
            $this->validateCreditData($data);

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
                        'purchase_price' => floatval(preg_replace('/[^0-9.]/', '', $detail['purchase_price'] ?? '0')),
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

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
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

            // Calcular interés si aplica
            $finalAmount = $remainingAmount;
            if (isset($data['interest_rate']) && $data['interest_rate'] > 0) {
                $interest = ($remainingAmount * floatval($data['interest_rate'])) / 100;
                $finalAmount = $remainingAmount + $interest;
            }

            // Crear el crédito
            $credit = parent::handleRecordCreation([
                'client_id' => $data['client_id'],
                'total_amount' => $finalAmount,
                'initial_payment' => $initialPayment,
                'interest_rate' => floatval($data['interest_rate'] ?? 0),
                'installments' => intval($data['installments'] ?? 0),
                'first_payment_date' => $data['first_payment_date'] ?? null,
                'status' => 'pending',
            ]);

            // Crear los detalles
            foreach ($data['details'] as $detail) {
                $credit->details()->create([
                    'provider_id' => $detail['provider_id'] ?? null,
                    'product_name' => $detail['product_name'],
                    'product_description' => $detail['product_description'] ?? null,
                    'identifier_type' => $detail['identifier_type'],
                    'identifier' => $detail['identifier'],
                    'quantity' => intval($detail['quantity']),
                    'unit_price' => floatval($detail['unit_price']),
                    'purchase_price' => floatval($detail['purchase_price']),
                    'subtotal' => floatval($detail['quantity']) * floatval($detail['unit_price']),
                ]);
            }

            // Crear las cuotas
            $this->createInstallments($credit, $finalAmount);

            DB::commit();
            return $credit;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en handleRecordCreation', [
                'message' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    protected function createInstallments($credit, float $totalAmount): void
    {
        $numberOfInstallments = $credit->installments;

        // Redondear el monto total
        $totalAmount = round($totalAmount, 2);

        // Calcular el monto base por cuota (sin redondear aún)
        $baseInstallmentAmount = $totalAmount / $numberOfInstallments;

        // Redondear al múltiplo de 100 más cercano
        $roundedInstallmentAmount = ceil($baseInstallmentAmount / 100) * 100;

        // Calcular el ajuste necesario para la última cuota
        $regularInstallmentsTotal = $roundedInstallmentAmount * ($numberOfInstallments - 1);
        $lastInstallmentAmount = $totalAmount - $regularInstallmentsTotal;

        // Asegurarse de que la última cuota no sea negativa
        if ($lastInstallmentAmount < 0) {
            $roundedInstallmentAmount = floor($totalAmount / $numberOfInstallments / 100) * 100;
            $regularInstallmentsTotal = $roundedInstallmentAmount * ($numberOfInstallments - 1);
            $lastInstallmentAmount = $totalAmount - $regularInstallmentsTotal;
        }

        $date = Carbon::parse($credit->first_payment_date);

        // Crear las cuotas
        for ($i = 1; $i <= $numberOfInstallments; $i++) {
            $amount = ($i == $numberOfInstallments) ? $lastInstallmentAmount : $roundedInstallmentAmount;

            $credit->installments()->create([
                'installment_number' => $i,
                'amount' => round($amount, 2), // Asegurar redondeo a 2 decimales
                'due_date' => $date->copy(),
                'status' => 'pending'
            ]);

            $date->addMonth();
        }
    }

    private function validateCreditData(array $data): void
    {
        if (!isset($data['client_id'], $data['installments'], $data['first_payment_date'])) {
            throw new \Exception('Datos incompletos para el crédito.');
        }

        if ($data['installments'] <= 0) {
            throw new \Exception('El número de cuotas debe ser mayor a 0.');
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}