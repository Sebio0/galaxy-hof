<?php

namespace App\Filament\Resources\EternalRankingResource\Pages;

use App\Filament\Resources\EternalRankingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEternalRankings extends ListRecords
{
    protected static string $resource = EternalRankingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
