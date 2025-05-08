<?php

namespace App\Filament\Resources\RankingTypeResource\Pages;

use App\Filament\Resources\RankingTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRankingTypes extends ListRecords
{
    protected static string $resource = RankingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
