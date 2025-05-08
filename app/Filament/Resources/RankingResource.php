<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RankingResource\Pages;
use App\Filament\Resources\RankingResource\RelationManagers;
use App\Models\Ranking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RankingResource extends Resource
{
    protected static ?string $model = Ranking::class;
    protected static ?string $label = 'Ranking';
    protected static ?string $pluralLabel = 'Rankings';
    protected static ?string $navigationGroup = 'Spiele';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('ranking_type.key_name')
                    ->label('Ranking Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('instance_round.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListRankings::route('/'),
            'create' => Pages\CreateRanking::route('/create'),
            'edit' => Pages\EditRanking::route('/{record}/edit'),
        ];
    }
}
