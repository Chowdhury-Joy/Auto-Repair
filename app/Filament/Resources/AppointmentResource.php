<?php

namespace App\Filament\Resources;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Mechanic;
use App\Models\ServiceBay;
use App\Services\CheckInService;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Appointment')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'id')
                        ->getOptionLabelFromRecordUsing(fn (Model $r) => $r->user?->name ?? "Customer #{$r->id}")
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('vehicle_id')
                        ->relationship('vehicle', 'id')
                        ->getOptionLabelFromRecordUsing(fn (Model $r) => "{$r->year} {$r->make} {$r->model}")
                        ->required(),
                    Forms\Components\Select::make('serviceTypes')
                        ->relationship('serviceTypes', 'name')
                        ->multiple()
                        ->preload()
                        ->required(),
                    Forms\Components\DateTimePicker::make('starts_at')->required()->seconds(false),
                    Forms\Components\DateTimePicker::make('ends_at')->required()->seconds(false),
                    Forms\Components\Select::make('service_bay_id')
                        ->label('Bay')
                        ->options(ServiceBay::active()->ordered()->pluck('name', 'id'))
                        ->required(),
                    Forms\Components\Select::make('mechanic_id')
                        ->label('Mechanic')
                        ->options(Mechanic::active()->ordered()->pluck('name', 'id'))
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                        ->default(AppointmentStatus::Scheduled->value)
                        ->required(),
                    Forms\Components\Textarea::make('customer_notes')->rows(2)->columnSpanFull(),
                    Forms\Components\Textarea::make('staff_notes')->rows(2)->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('When')
                    ->dateTime('D M j · g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.display_name')
                    ->label('Vehicle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serviceTypes.name')
                    ->label('Services')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('serviceBay.name')
                    ->label('Bay')
                    ->sortable(),
                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Mechanic')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (AppointmentStatus $s) => $s->color())
                    ->formatStateUsing(fn (AppointmentStatus $s) => $s->label())
                    ->sortable(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $q) => $q->whereDate('starts_at', today())),
                Filter::make('this_week')
                    ->label('This week')
                    ->query(fn (Builder $q) => $q
                        ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])),
                SelectFilter::make('status')
                    ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->multiple(),
                SelectFilter::make('service_bay_id')
                    ->label('Bay')
                    ->options(ServiceBay::ordered()->pluck('name', 'id')),
                SelectFilter::make('mechanic_id')
                    ->label('Mechanic')
                    ->options(Mechanic::ordered()->pluck('name', 'id')),
            ])
            ->actions([
                // Check in: creates WorkOrder & updates appointment status
                Tables\Actions\Action::make('checkIn')
                    ->label('Check in')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $r) => $r->status === AppointmentStatus::Scheduled)
                    ->action(fn (Appointment $r) => app(CheckInService::class)->checkIn($r)),

                // Mark no-show.
                Tables\Actions\Action::make('noShow')
                    ->label('No-show')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $r) => $r->status === AppointmentStatus::Scheduled)
                    ->action(fn (Appointment $r) => $r->update(['status' => AppointmentStatus::NoShow])),

                // Cancel.
                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $r) => ! $r->status->isNonOccupying()
                        && $r->status !== AppointmentStatus::Completed)
                    ->action(fn (Appointment $r) => $r->update(['status' => AppointmentStatus::Cancelled])),

                // Reassign bay + mechanic without touching status.
                Tables\Actions\Action::make('reassign')
                    ->label('Reassign')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('service_bay_id')
                            ->label('Bay')
                            ->options(ServiceBay::active()->ordered()->pluck('name', 'id'))
                            ->default(fn (Appointment $r) => $r->service_bay_id)
                            ->required(),
                        Forms\Components\Select::make('mechanic_id')
                            ->label('Mechanic')
                            ->options(Mechanic::active()->ordered()->pluck('name', 'id'))
                            ->default(fn (Appointment $r) => $r->mechanic_id)
                            ->required(),
                    ])
                    ->action(function (Appointment $r, array $data) {
                        $r->update([
                            'service_bay_id' => $data['service_bay_id'],
                            'mechanic_id' => $data['mechanic_id'],
                        ]);
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
