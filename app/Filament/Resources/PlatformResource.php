<?php

namespace App\Filament\Resources;

use App\Models\Platform;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\PlatformResource\Pages;

class PlatformResource extends Resource
{
    protected static ?string $model = Platform::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog'; // Cambiar a un icono válido
    protected static ?string $navigationGroup = 'Configuración';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre de la Plataforma')
                ->required(),
            Forms\Components\FileUpload::make('logo')
                ->label('Logo de la Plataforma')
                ->image()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nombre')
                ->sortable(),
            Tables\Columns\ImageColumn::make('logo')
                ->label('Logo')
                ->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatforms::route('/'),
            'create' => Pages\CreatePlatform::route('/create'),
            'edit' => Pages\EditPlatform::route('/{record}/edit'),
        ];
    }
}
