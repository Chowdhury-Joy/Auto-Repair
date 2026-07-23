<?php

namespace App\Filament\Resources\ServiceBayResource\Pages;

use App\Filament\Resources\ServiceBayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceBays extends ListRecords
{
    protected static string $resource = ServiceBayResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
