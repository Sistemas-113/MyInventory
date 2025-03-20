<?php

namespace App\Filament\Resources;

use App\Models\Client;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use App\Filament\Resources\ClientResource\Pages;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientsExport;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Ventas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información Personal')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombres')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('identification')
                        ->label('Cédula')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),
                    
                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->required()
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
                    Forms\Components\Textarea::make('references')
                        ->label('Referencias')
                        ->maxLength(500),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        return Excel::download(new ClientsExport(), 'clientes-' . now()->format('Y-m-d') . '.xlsx');
                    })
            ])
            ->columns([
                Tables\Columns\TextColumn::make('identification')
                    ->label('Cédula')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Cliente')
                    ->modalDescription('¿Está seguro que desea eliminar este cliente?')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->action(function (Client $record) {
                        // Verificar ventas primero
                        if ($record->sales()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('No se puede eliminar')
                                ->body('El cliente tiene ventas registradas.')
                                ->send();

                            throw new Halt(); // Cambiado aquí
                        }

                        // Verificar créditos pendientes
                        if ($record->sales()->where('payment_type', 'credit')
                            ->where('status', 'pending')
                            ->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('No se puede eliminar')
                                ->body('El cliente tiene créditos pendientes.')
                                ->send();

                            throw new Halt(); // Cambiado aquí
                        }

                        // Si pasa las validaciones, eliminar
                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Cliente eliminado')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Exportar Seleccionados')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            return Excel::download(
                                new ClientsExport($records), 
                                'clientes-seleccionados-' . now()->format('Y-m-d') . '.xlsx'
                            );
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar Clientes')
                        ->modalDescription('¿Está seguro que desea eliminar estos clientes?')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                if ($record->sales()->exists()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('No se puede eliminar')
                                        ->body('Uno o más clientes tienen registros asociados.')
                                        ->send();

                                    throw new Halt(); // Cambiado aquí
                                }

                                if ($record->sales()->where('payment_type', 'credit')
                                    ->where('status', 'pending')
                                    ->exists()) {
                                    Notification::make()
                                        ->danger()
                                        ->title('No se puede eliminar')
                                        ->body('Uno o más clientes tienen créditos pendientes.')
                                        ->send();

                                    throw new Halt(); // Cambiado aquí
                                }
                            }

                            // Si pasa todas las validaciones, eliminar
                            $records->each->delete();

                            Notification::make()
                                ->success()
                                ->title('Clientes eliminados')
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No hay clientes registrados')
            ->emptyStateDescription('Los clientes que registres aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-users')
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSortInSession();
    }

    public static function getModelLabel(): string
    {
        return 'Cliente';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Clientes';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
