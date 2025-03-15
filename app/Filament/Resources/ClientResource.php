<?php

namespace App\Filament\Resources;

use App\Models\Client;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\ClientResource\Pages;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Ventas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nombre'),
            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255),
            Forms\Components\DatePicker::make('birth_date')
                ->required()
                ->label('Fecha de Nacimiento'),
            Forms\Components\TextInput::make('credit_limit')
                ->required()
                ->numeric()
                ->prefix('$')
                ->label('Límite de Crédito'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable()->searchable()->label('Nombre'),
            Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('credit_limit')->money('usd')->label('Límite Crédito'),
            Tables\Columns\TextColumn::make('current_balance')->money('usd')->label('Balance'),
            Tables\Columns\BadgeColumn::make('current_balance')
                ->color(fn ($record) => $record->current_balance > 0 ? 'warning' : 'success')
                ->label('Estado'),
        ]);
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
