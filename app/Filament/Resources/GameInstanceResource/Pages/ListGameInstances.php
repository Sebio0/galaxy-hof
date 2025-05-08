<?php

namespace App\Filament\Resources\GameInstanceResource\Pages;

use App\Filament\Resources\GameInstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameInstances extends ListRecords
{
    protected static string $resource = GameInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
