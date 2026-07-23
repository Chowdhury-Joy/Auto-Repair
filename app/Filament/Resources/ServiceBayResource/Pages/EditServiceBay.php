<?php

namespace App\Filament\Resources\ServiceBayResource\Pages;

use App\Filament\Resources\ServiceBayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceBay extends EditRecord
{
    protected static string $resource = ServiceBayResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
