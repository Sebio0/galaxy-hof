<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EternalRankingPlayerResource\Pages;
use App\Models\EternalRankingPlayer;
use App\Models\EternalRankingResult;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;


class EternalRankingPlayerResource extends Resource
{
    protected static ?string $model = EternalRankingPlayer::class;

    protected static ?string $label = 'Eternal Ranking Player';
    protected static ?string $pluralLabel = 'Eternal Ranking Players';
    protected static ?string $navigationGroup = 'Galaxy-Network';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $model): bool
    {
        return false;
    }

    public static function canDelete(Model $model): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email_hash')
                    ->label('E-Mail Hash')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('nickname')
                    ->label('Nickname')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])->filters([
                //
            ])->headerActions([
                Action::make('Merge Players')
                    ->label('Spieler zusammenführen')
                    ->icon('heroicon-o-arrow-path')
                    ->url(EternalRankingPlayerResource::getUrl('merge')),
            ])->actions([
                //
            ])->bulkActions([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('mergePlayers')
                        ->label('Spieler zusammenführen')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->form([
                            Select::make('target_email')
                                ->label('Zielspieler (E-Mail)')
                                ->required()
                                ->options(fn(Collection $records) => $records
                                    ->mapWithKeys(fn($record) => [$record->email_hash => $record->nickname . ' (' . $record->email_hash . ')']))
                                ->searchable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $targetHash = $data['target_email'];
                            $target = EternalRankingPlayer::where('email_hash', $targetHash)->first();

                            if (!$target) return;

                            foreach ($records as $player) {
                                if ($player->id === $target->id) continue;

                                EternalRankingResult::where('player_id', $player->id)
                                    ->update(['player_id' => $target->id]);

                                $player->delete();
                            }
                        })
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEternalRankingPlayers::route('/'),
            'create' => Pages\CreateEternalRankingPlayer::route('/create'),
            'edit' => Pages\EditEternalRankingPlayer::route('/{record}/edit'),
            'merge' => Pages\MergePlayers::route('/merge'), // ✅ So ist es korrekt
        ];
    }
}
