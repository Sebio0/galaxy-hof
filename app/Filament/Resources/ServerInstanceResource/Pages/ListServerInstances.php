<?php

namespace App\Filament\Resources\ServerInstanceResource\Pages;

use App\Filament\Resources\ServerInstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerInstances extends ListRecords
{
    protected static string $resource = ServerInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
