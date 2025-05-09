<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameInstanceResource\Pages;
use App\Filament\Resources\GameInstanceResource\RelationManagers;
use App\Models\GameInstance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameInstanceResource extends Resource
{
    protected static ?string $model = GameInstance::class;
    protected static ?string $label = 'Spiel-Instanz';
    protected static ?string $pluralLabel = 'Spiel-Instanzen';
    protected static ?string $navigationGroup = 'Galaxy-Network';
    protected static ?string $navigationLabel = 'Spiel-Instanzen';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('server_instance_id')
                    ->label('Server-Instance ID')
                    ->required()
                    ->relationship('serverInstance', 'server_name')
                    ->searchable()
                    ->preload()
                    ->reactive()
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
                Tables\Columns\TextColumn::make('serverInstance.server_name')
                    ->label('Server-Instance')
                    ->sortable()
                    ->searchable(),
            ])->filters([
                //
            ])->headerActions([
                Tables\Actions\CreateAction::make(),
            ])->actions([
                //
            ])->bulkActions([
                //
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
            'index' => Pages\ListGameInstances::route('/'),
            'create' => Pages\CreateGameInstance::route('/create'),
            'edit' => Pages\EditGameInstance::route('/{record}/edit'),
        ];
    }
}
