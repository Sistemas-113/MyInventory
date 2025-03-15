<?php

namespace App\Filament\Resources;

use App\Models\Product;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\ProductResource\Pages;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Inventario';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')
                ->required()
                ->label('Categoría'),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nombre'),
            Forms\Components\Textarea::make('description')
                ->label('Descripción'),
            Forms\Components\TextInput::make('price')
                ->required()
                ->numeric()
                ->prefix('$')
                ->label('Precio'),
            Forms\Components\TextInput::make('stock')
                ->required()
                ->numeric()
                ->label('Stock'),
            Forms\Components\TextInput::make('min_stock')
                ->required()
                ->numeric()
                ->label('Stock Mínimo'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable()->searchable()->label('Nombre'),
            Tables\Columns\TextColumn::make('category.name')->sortable()->label('Categoría'),
            Tables\Columns\TextColumn::make('price')->money('usd')->sortable()->label('Precio'),
            Tables\Columns\TextColumn::make('stock')->sortable()->label('Stock'),
            Tables\Columns\BadgeColumn::make('stock')
                ->color(fn ($record) => $record->needsRestock() ? 'danger' : 'success')
                ->label('Estado Stock'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
