<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RankingResource\Pages;
use App\Filament\Resources\RankingResource\RelationManagers;
use App\Models\InstanceRound;
use App\Models\Ranking;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RankingResource extends Resource
{
    protected static ?string $model = Ranking::class;
    protected static ?string $label = 'Ranking';
    protected static ?string $pluralLabel = 'Rankings';
    protected static ?string $navigationGroup = 'Galaxy-Network';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    /**
     * @throws \Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('rankingType.display_name')
                    ->label('Ranking Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('hof.round.name')
                    ->label('Runde')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.nickname')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('rankingType')
                    ->relationship('rankingType', 'display_name')
                    ->multiple()
                    ->preload(),
                Filter::make('round')
                    ->label('Runde')
                    ->form([
                        Select::make('round_id')
                            ->multiple()
                            ->label('Runde')
                            ->searchable()
                            ->preload()
                            ->options(fn() => InstanceRound::pluck('name', 'id')),
                    ])
                    ->query(function (Builder $query, array $state) {
                        if (!empty($state['round_id'])) {
                            $query->whereHas('hof.round', function ($q) use ($state) {
                                $q->whereIn('id', $state['round_id']);
                            });
                        }
                        return $query;
                    }),
            ])
            ->actions([
            ])
            ->bulkActions([
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
            'index' => Pages\ListRankings::route('/'),
            'create' => Pages\CreateRanking::route('/create'),
            'edit' => Pages\EditRanking::route('/{record}/edit'),
        ];
    }
}
