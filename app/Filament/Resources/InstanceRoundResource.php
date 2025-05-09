<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstanceRoundResource\Pages;
use App\Filament\Resources\InstanceRoundResource\RelationManagers;
use App\Models\GameInstance;
use App\Models\InstanceRound;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstanceRoundResource extends Resource
{
    protected static ?string $model = InstanceRound::class;
    protected static ?string $label = 'Runde';
    protected static ?string $pluralLabel = 'Runden';
    protected static ?string $navigationGroup = 'Galaxy-Network';
    protected static ?string $navigationLabel = 'Runden';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('game_instance_id')
                    ->required()
                    ->relationship('instance', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Spiel-Instanz'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Rundenname')
                    ->maxLength(255)
                    ->unique(InstanceRound::class, 'name', ignoreRecord: true),
                Forms\Components\DateTimePicker::make('start_date')
                    ->required()
                    ->label('Startdatum')
                    ->placeholder('YYYY-MM-DD HH:MM:SS'),
                Forms\Components\DateTimePicker::make('end_date')
                    ->label('Enddatum')
                    ->placeholder('YYYY-MM-DD HH:MM:SS'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('instance.name')->label('Spiel-Instanz')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('start_date')->label('Startdatum')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('Enddatum')->sortable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->striped()
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
            'index' => Pages\ListInstanceRounds::route('/'),
            'create' => Pages\CreateInstanceRound::route('/create'),
            'edit' => Pages\EditInstanceRound::route('/{record}/edit'),
        ];
    }
}
