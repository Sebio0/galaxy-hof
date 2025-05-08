<?php

namespace App\Filament\Resources\HofUserResource\Pages;

use App\Filament\Resources\HofUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHofUsers extends ListRecords
{
    protected static string $resource = HofUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
