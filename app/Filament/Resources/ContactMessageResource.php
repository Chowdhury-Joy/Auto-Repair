<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Read-only-by-design: messages only ever originate from the public /contact
 * form (routes/web.php's `contact.submit` handler), so there's no "create" page —
 * staff can view and mark-handled here, not author new ones.
 */
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'Customer Requests';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('message')
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'handled' ? 'success' : 'warning'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['new' => 'New', 'handled' => 'Handled']),
            ])
            ->actions([
                Actions\Action::make('markHandled')
                    ->label('Mark handled')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ContactMessage $r) => $r->status !== 'handled')
                    ->action(fn (ContactMessage $r) => $r->update(['status' => 'handled'])),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
        ];
    }
}
