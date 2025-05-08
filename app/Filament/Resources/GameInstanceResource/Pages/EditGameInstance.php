<?php

namespace App\Filament\Resources\GameInstanceResource\Pages;

use App\Filament\Resources\GameInstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameInstance extends EditRecord
{
    protected static string $resource = GameInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
