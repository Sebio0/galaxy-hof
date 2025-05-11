<?php

namespace App\Filament\Resources\EternalRankingResource\Pages;

use App\Filament\Resources\EternalRankingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEternalRanking extends EditRecord
{
    protected static string $resource = EternalRankingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
