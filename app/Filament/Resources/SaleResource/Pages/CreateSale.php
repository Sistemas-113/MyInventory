<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\SaleService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Log;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            Log::debug('Datos del formulario antes de crear', ['data' => $data]);
            
            // Validar datos requeridos
            if (!isset($data['client_id'], $data['payment_type'])) {
                Log::warning('Datos incompletos', ['data' => $data]);
                throw new Halt('Datos incompletos para la venta.');
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Error en mutateFormDataBeforeCreate', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // Validar datos críticos
            if (!isset($data['client_id'], $data['payment_type'])) {
                throw new Halt('Faltan datos básicos de la venta');
            }

            // Validar datos de crédito si aplica
            if ($data['payment_type'] === 'credit') {
                if (!isset($data['interest_rate'], $data['installments'], $data['first_payment_date'])) {
                    throw new Halt('Faltan datos del crédito');
                }
            }

            // Validar total
            if (!isset($data['total_amount']) || $data['total_amount'] <= 0) {
                throw new Halt('El total de la venta debe ser mayor a cero');
            }

            Log::info('Datos de venta validados', [
                'data' => array_merge($data, ['total_amount' => $data['total_amount']])
            ]);

            Log::info('Iniciando creación de venta', [
                'form_data' => $data,
                'user' => auth()->user()->name ?? 'unknown'
            ]);

            $sale = app(SaleService::class)->createSale($data);

            Log::info('Venta creada exitosamente', [
                'sale_id' => $sale->id,
                'client_id' => $sale->client_id,
                'total' => $sale->total_amount
            ]);

            return $sale;

        } catch (\Exception $e) {
            Log::error('Error en handleRecordCreation', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
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
}
