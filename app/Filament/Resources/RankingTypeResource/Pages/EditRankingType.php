<?php

namespace App\Filament\Resources\RankingTypeResource\Pages;

use App\Filament\Resources\RankingTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRankingType extends EditRecord
{
    protected static string $resource = RankingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
