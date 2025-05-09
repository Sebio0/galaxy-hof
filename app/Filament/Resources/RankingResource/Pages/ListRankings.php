<?php

namespace App\Filament\Resources\RankingResource\Pages;

use App\Filament\Resources\RankingResource;
use Closure;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRankings extends ListRecords
{
    protected static string $resource = RankingResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
    protected function makeTable(): \Filament\Tables\Table
    {
        return parent::makeTable()->recordUrl(null);
    }
}
