<?php

namespace App\Filament\Resources;

use App\Enums\AlertType;
use App\Filament\Resources\OperationsAlertResource\Pages;
use App\Models\OperationsAlert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class OperationsAlertResource extends Resource
{
    protected static ?string $model = OperationsAlert::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';
    protected static string | UnitEnum | null $navigationGroup = 'System';
    protected static ?int $navigationSort = 99;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Triggered')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (AlertType $s) => $s->label()),
                Tables\Columns\TextColumn::make('reference_type')
                    ->formatStateUsing(fn ($s) => class_basename($s)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($s) => match($s) {
                        'pending' => 'warning',
                        'delivered' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('delivered_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('error_message')->limit(50)->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOperationsAlerts::route('/')];
    }
}
