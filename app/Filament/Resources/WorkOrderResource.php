<?php

namespace App\Filament\Resources;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource\Pages;
use App\Models\WorkOrder;
use App\Services\WorkOrderCompletionService;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string | UnitEnum | null $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options(collect(WorkOrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                        ->required(),
                    Forms\Components\Select::make('mechanic_id')
                        ->relationship('mechanic', 'name')
                        ->searchable(),
                    Forms\Components\DateTimePicker::make('opened_at')->disabled(),
                    Forms\Components\DateTimePicker::make('completed_at')->disabled(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(2),

            Section::make('Line Items')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('description')->required()->columnSpan(2),
                            Forms\Components\Select::make('type')
                                ->options(['labor' => 'Labor', 'part' => 'Part'])
                                ->default('labor')
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()->default(1)->minValue(0.01)->required(),
                            Forms\Components\TextInput::make('rate_cents')
                                ->label('Rate (cents)')
                                ->numeric()->required(),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->reorderable('sort_order')
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opened_at')->label('Opened')->dateTime('M j · g:i A')->sortable(),
                Tables\Columns\TextColumn::make('customer.user.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('vehicle.display_name')->label('Vehicle'),
                Tables\Columns\TextColumn::make('mechanic.name')->label('Mechanic'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (WorkOrderStatus $s) => $s->color())
                    ->formatStateUsing(fn (WorkOrderStatus $s) => $s->label()),
                Tables\Columns\TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn ($s) => '$' . number_format($s / 100, 2))
                    ->sortable(),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(WorkOrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->multiple(),
            ])
            ->actions([
                // Progress the status easily.
                Tables\Actions\Action::make('progressStatus')
                    ->label('Move to In Progress')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn (WorkOrder $r) => in_array($r->status, [WorkOrderStatus::Open, WorkOrderStatus::AwaitingParts]))
                    ->action(fn (WorkOrder $r) => $r->update(['status' => WorkOrderStatus::InProgress])),

                Tables\Actions\Action::make('readyForPickup')
                    ->label('Ready for Pickup')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (WorkOrder $r) => $r->status === WorkOrderStatus::InProgress)
                    ->action(fn (WorkOrder $r) => $r->update(['status' => WorkOrderStatus::ReadyForPickup])),

                Tables\Actions\Action::make('completeAndInvoice')
                    ->label('Complete & Invoice')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (WorkOrder $r) => ! $r->status->isTerminal() && $r->invoice()->doesntExist())
                    ->action(function (WorkOrder $r) {
                        app(WorkOrderCompletionService::class)->complete($r);
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'edit'   => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
