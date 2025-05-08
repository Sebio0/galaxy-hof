<?php

namespace App\Filament\Resources\ServerInstanceResource\Pages;

use App\Filament\Resources\ServerInstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerInstance extends EditRecord
{
    protected static string $resource = ServerInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
