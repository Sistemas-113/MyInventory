<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar Cliente')
                ->modalHeading('Eliminar Cliente')
                ->modalDescription('¿Está seguro que desea eliminar este cliente? Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->modalCancelActionLabel('No, cancelar')
                ->successNotificationTitle('Cliente eliminado correctamente'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cliente actualizado')
            ->body('El cliente ha sido actualizado exitosamente.');
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save'),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url($this->getResource()::getUrl('index')),
            Actions\DeleteAction::make()
                ->label('Eliminar Cliente'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
