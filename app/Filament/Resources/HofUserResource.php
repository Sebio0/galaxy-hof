<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HofUserResource\Pages;
use App\Filament\Resources\HofUserResource\RelationManagers;
use App\Models\HofUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HofUserResource extends Resource
{
    protected static ?string $model = HofUser::class;
    protected static ?string $label = 'Hof User';
    protected static ?string $pluralLabel = 'Hof Users';
    protected static ?string $navigationGroup = 'Galaxy-Network';
    protected static ?string $navigationLabel = 'Hof Users';
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
                Tables\Columns\TextColumn::make('nickname')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('coordinates')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('alliance_tag')
                    ->sortable()
                    ->searchable(),
            ])->filters([
                //
            ])->headerActions([
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
            'index' => Pages\ListHofUsers::route('/'),
            'create' => Pages\CreateHofUser::route('/create'),
            'edit' => Pages\EditHofUser::route('/{record}/edit'),
        ];
    }
}
