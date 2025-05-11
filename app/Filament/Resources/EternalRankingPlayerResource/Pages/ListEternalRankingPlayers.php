<?php

namespace App\Filament\Resources\EternalRankingPlayerResource\Pages;

use App\Filament\Resources\EternalRankingPlayerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEternalRankingPlayers extends ListRecords
{
    protected static string $resource = EternalRankingPlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
