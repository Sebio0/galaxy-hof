<?php

namespace App\Filament\Resources\InstanceRoundResource\Pages;

use App\Filament\Resources\InstanceRoundResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstanceRound extends EditRecord
{
    protected static string $resource = InstanceRoundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
