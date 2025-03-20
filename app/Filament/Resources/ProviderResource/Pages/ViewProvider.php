<?php

namespace App\Filament\Resources\ProviderResource\Pages;

use App\Filament\Resources\ProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProvider extends ViewRecord
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar'),
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->modalHeading('Eliminar Proveedor')
                ->modalDescription('¿Está seguro que desea eliminar este proveedor?')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->modalCancelActionLabel('No, cancelar'),
        ];
    }
}
