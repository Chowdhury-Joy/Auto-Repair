<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceTypeResource\Pages;
use App\Models\ServiceType;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ServiceTypeResource extends Resource
{
    protected static ?string $model = ServiceType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Shop Resources';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'Service Menu';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')->required()->maxLength(100),
            Forms\Components\Textarea::make('description')->rows(2),
            Forms\Components\TextInput::make('duration_minutes')
                ->label('Typical duration (minutes)')
                ->numeric()->required()->minValue(15)->step(15),
            Forms\Components\TextInput::make('price_range_min_cents')
                ->label('Price range — min ($)')
                ->numeric()->required(),
            Forms\Components\TextInput::make('price_range_max_cents')
                ->label('Price range — max ($)')
                ->numeric()->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Minutes')->sortable(),
                Tables\Columns\TextColumn::make('price_range_min_cents')
                    ->label('Min')->formatStateUsing(fn ($s) => '$'.number_format($s / 100, 0)),
                Tables\Columns\TextColumn::make('price_range_max_cents')
                    ->label('Max')->formatStateUsing(fn ($s) => '$'.number_format($s / 100, 0)),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceTypes::route('/'),
            'create' => Pages\CreateServiceType::route('/create'),
            'edit' => Pages\EditServiceType::route('/{record}/edit'),
        ];
    }
}
