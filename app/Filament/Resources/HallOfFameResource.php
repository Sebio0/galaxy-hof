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
    protected static ?string $label = 'Hall of Fame';
    protected static ?string $pluralLabel = 'Hall of Fames';
    protected static ?string $navigationGroup = 'Galaxy-Network';
    protected static ?string $navigationLabel = 'Hall of Fame';

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
                Forms\Components\DateTimePicker::make('round.end_date')
                    ->required()
                    ->label('Enddatum')
                    ->placeholder('YYYY-MM-DD HH:MM:SS')
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
                    ->getStateUsing(function($record) {
                        return $record->instance->name ?? 'N/A';
                    })
                    ->sortable()
                    ->searchable(),
            ])->defaultSort('created_at', 'desc')
            ->filters([
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
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
