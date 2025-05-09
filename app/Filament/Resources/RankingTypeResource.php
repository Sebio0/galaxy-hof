<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RankingTypeResource\Pages;
use App\Filament\Resources\RankingTypeResource\RelationManagers;
use App\Models\RankingType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RankingTypeResource extends Resource
{
    protected static ?string $model = RankingType::class;
    protected static ?string $label = 'Ranking-Typ';
    protected static ?string $pluralLabel = 'Ranking-Typen';
    protected static ?string $navigationGroup = 'Galaxy-Network';
    protected static ?string $navigationLabel = 'Ranking-Typen';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('display_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options(
                        ['resource' => 'Ressource',
                            'fleet' => 'Schiffe',
                            'defense' => 'Geschütze',
                            'misc' => 'Rest']
                    )
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('key_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextInputColumn::make('display_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\SelectColumn::make('type')
                    ->options(
                        ['resource' => 'Ressource',
                            'fleet' => 'Schiffe',
                            'defense' => 'Geschütze',
                            'misc' => 'Rest'])
                    ->sortable()
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListRankingTypes::route('/'),
            'create' => Pages\CreateRankingType::route('/create'),
            'edit' => Pages\EditRankingType::route('/{record}/edit'),
        ];
    }
}
