<?php

namespace App\Filament\Resources\EternalRankingPlayerResource\Pages;

use App\Filament\Resources\EternalRankingPlayerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEternalRankingPlayer extends EditRecord
{
    protected static string $resource = EternalRankingPlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
