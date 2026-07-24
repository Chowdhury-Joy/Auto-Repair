<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('number')->disabled(),
            Forms\Components\Select::make('status')
                ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                ->required(),
            Forms\Components\TextInput::make('total_cents')
                ->label('Total ($)')
                ->formatStateUsing(fn ($state) => '$'.number_format(((int) $state) / 100, 2))
                ->disabled(),
            Forms\Components\DateTimePicker::make('paid_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.user.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    // Parameter must be named $state — see AppointmentResource for why
                    // (Filament matches this closure's injected value by parameter name).
                    ->color(fn (InvoiceStatus $state) => $state->color())
                    ->formatStateUsing(fn (InvoiceStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('issued_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('paid_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\Action::make('markSent')
                    ->visible(fn (Invoice $r) => $r->status === InvoiceStatus::Draft)
                    ->action(fn (Invoice $r) => $r->update(['status' => InvoiceStatus::Sent, 'issued_at' => now()])),
                Actions\Action::make('markPaid')
                    ->color('success')
                    ->visible(fn (Invoice $r) => $r->status !== InvoiceStatus::Paid)
                    ->action(fn (Invoice $r) => $r->update(['status' => InvoiceStatus::Paid, 'paid_at' => now()])),
                Actions\Action::make('viewPublic')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Invoice $r) => $r->publicUrl())
                    ->openUrlInNewTab(),
                Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
