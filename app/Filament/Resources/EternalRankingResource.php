<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EternalRankingResource\Pages;
use App\Filament\Resources\EternalRankingResource\RelationManagers;
use App\Jobs\ImportRankingJob;
use App\Models\EternalRanking;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Collection;

class EternalRankingResource extends Resource
{
    protected static ?string $model = EternalRanking::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Ewige HoF';
    protected static ?string $pluralLabel = 'Ewige HoF';
    protected static ?string $modelLabel = 'Runde';
    protected static ?string $navigationGroup = 'Galaxy-Network';

    public static function form(Forms\Form $form): Forms\Form
    {
        $maxRound = EternalRanking::query()->max('round_number');
        $nextNumber = $maxRound ? ($maxRound + 1) : 1;

        return $form
            ->schema([
                TextInput::make('round_name')
                    ->label('Rundenname')
                    ->required()
                    ->placeholder('Runde ' . $nextNumber)
                    ->default('Runde ' . $nextNumber)
                    ->rules(['required', 'regex:/^Runde\s*\d+$/i'])
                    ->reactive()
                    ->afterStateHydrated(function ($state, $set) use ($nextNumber) {
                        if (!$state) {
                            $set('round_name', 'Runde ' . $nextNumber);
                        }
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (preg_match('/Runde\s*(\d+)/i', $state, $matches)) {
                            $set('round_number', (int) $matches[1]);
                        }
                    }),

                TextInput::make('round_number')
                    ->label('Runden-Nummer')
                    ->numeric()
                    ->readOnly()
                    ->required()
                    ->default($nextNumber)
                    ->afterStateHydrated(function ($state, $set) use ($nextNumber) {
                        if (!$state) {
                            $set('round_number', $nextNumber);
                        }
                    }),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('round_number')->label('Runde #'),
                Tables\Columns\TextColumn::make('round_name')->label('Rundenname'),
                Tables\Columns\TextColumn::make('created_at')->date()->label('Erstellt'),
                Tables\Columns\TextColumn::make('updated_at')->date()->label('Aktualisiert'),
            ])
            ->defaultSort('round_number', 'desc')
            ->actions([
                Action::make('refreshHof')
                    ->label('Ewige HoF aktualisieren')
                    ->requiresConfirmation()
                    ->color('warning')
                    ->action(function (EternalRanking $record) {
                        ImportRankingJob::dispatch($record->round_name, $record->round_number);
                       Notification::make()
                            ->title("Import für {$record->round_name} gestartet")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulkRefresh')
                    ->label('Mehrere aktualisieren')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            ImportRankingJob::dispatch($record->round_name, $record->round_number);
                        }
                        Notification::make()
                            ->title('Import-Jobs für ausgewählte Runden gestartet')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->color('warning'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEternalRankings::route('/'),
            'create'=> Pages\CreateEternalRanking::route('/create'),
            'edit'  => Pages\EditEternalRanking::route('/{record}/edit'),
        ];
    }
}
