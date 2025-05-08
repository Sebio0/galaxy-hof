<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HallOfFameResource\Pages;
use App\Filament\Resources\HallOfFameResource\RelationManagers;
use App\Models\HallOfFame;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HallOfFameResource extends Resource
{
    protected static ?string $model = HallOfFame::class;
    protected static ?string $label = 'Hall-Of-Fame';
    protected static ?string $pluralLabel = 'Hall-Of-Fames';
    protected static ?string $navigationGroup = 'Spiele';
    protected static ?string $navigationLabel = 'Hall-Of-Fames';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('instance_round_id')
                    ->required()
                    ->relationship('round', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Spiel-Runde'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('instance.name')
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
            'index' => Pages\ListHallOfFames::route('/'),
            'create' => Pages\CreateHallOfFame::route('/create'),
            'edit' => Pages\EditHallOfFame::route('/{record}/edit'),
        ];
    }
}
