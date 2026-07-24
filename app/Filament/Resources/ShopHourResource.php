<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopHourResource\Pages;
use App\Models\ShopHour;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ShopHourResource extends Resource
{
    protected static ?string $model = ShopHour::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Shop Resources';

    protected static ?int $navigationSort = 13;

    protected static ?string $navigationLabel = 'Shop Hours';

    // Only 7 rows ever exist — edit-in-place on the list.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return $schema->components([
            Forms\Components\Select::make('day_of_week')
                ->options(array_combine(range(1, 7), $days))
                ->required(),
            Forms\Components\Toggle::make('is_closed'),
            Forms\Components\TimePicker::make('opens_at')->seconds(false)->nullable(),
            Forms\Components\TimePicker::make('closes_at')->seconds(false)->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $days = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->formatStateUsing(fn ($state) => $days[$state] ?? $state)->sortable(),
                Tables\Columns\IconColumn::make('is_closed')->boolean(),
                Tables\Columns\TextColumn::make('opens_at'),
                Tables\Columns\TextColumn::make('closes_at'),
            ])
            ->defaultSort('day_of_week')
            ->actions([Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShopHours::route('/'),
            'edit' => Pages\EditShopHour::route('/{record}/edit'),
        ];
    }
}
