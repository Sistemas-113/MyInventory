<?php

namespace App\Filament\Resources;

use App\Models\Provider;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use App\Filament\Resources\ProviderResource\Pages;
use Filament\Notifications\Notification;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $modelLabel = 'Proveedor';
    protected static ?string $pluralModelLabel = 'Proveedores';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del Proveedor')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->maxLength(255),
                ])->columns(2),

            Forms\Components\Section::make('Información Adicional')
                ->schema([
                    Forms\Components\Textarea::make('address')
                        ->label('Dirección')
                        ->maxLength(500),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->maxLength(500),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->modalHeading('Eliminar Proveedor')
                    ->modalDescription('¿Está seguro que desea eliminar este proveedor?')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('No, cancelar')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Proveedor eliminado')
                            ->body('El proveedor ha sido eliminado exitosamente.')
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
            'view' => Pages\ViewProvider::route('/{record}'),
        ];
    }
}
