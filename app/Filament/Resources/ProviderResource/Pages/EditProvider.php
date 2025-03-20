<?php

namespace App\Filament\Resources\ProviderResource\Pages;

use App\Filament\Resources\ProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->modalHeading('Eliminar Proveedor')
                ->modalDescription('¿Está seguro que desea eliminar este proveedor?')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->modalCancelActionLabel('No, cancelar'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Proveedor actualizado')
            ->body('El proveedor ha sido actualizado exitosamente.');
    }
}
