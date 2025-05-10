<?php

namespace App\Filament\Resources\HallOfFameResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Filament\Resources\HallOfFameResource;
use App\Models\InstanceRound;
use App\Jobs\GenerateHallOfFameJob;
use App\Models\HallOfFame;

class CreateHallOfFame extends CreateRecord
{
    protected static string $resource = HallOfFameResource::class;

    /**
     * Before creating HallOfFame, update the selected round's end_date.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $round = InstanceRound::findOrFail($data['instance_round_id']);
        $round->end_date = Carbon::parse($data['round']['end_date']);
        $round->save();

        // Prepare HallOfFame attributes
        return [
            'id'                => Str::uuid()->toString(),
            'instance_round_id' => $round->id,
            'name'              => $round->name,
        ];
    }

    /**
     * After creating HallOfFame, dispatch the import job.
     */
    protected function afterCreate(): void
    {
        $hof = $this->record;
        $round = InstanceRound::findOrFail($hof->instance_round_id);

        GenerateHallOfFameJob::dispatch([
            'game_instance_id' => $round->game_instance_id,
            'round_id'         => $round->id,
            'hof_id'           => $hof->id,
            'round_name'       => $round->name,
            'start_date'       => Carbon::parse($round->start_date),
            'end_date'         => Carbon::parse($round->end_date),
        ]);
    }
}
