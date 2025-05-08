<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerInstanceResource\Pages;
use App\Filament\Resources\ServerInstanceResource\RelationManagers;
use App\Models\ServerInstance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServerInstanceResource extends Resource
{
    protected static ?string $model = ServerInstance::class;
    protected static ?string $pluralLabel = 'Server-Instanzen';
    protected static ?string $label = 'Server-Instanz';
    protected static ?string $navigationGroup = 'Server Management';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('server_name')
                    ->label('Serverbezeichnung')
                    ->helperText('Ein eindeutiger Name zur Identifikation dieser Server-Instanz')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. Hauptserver oder Testumgebung')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('database_host')
                    ->label('Datenbank-Host')
                    ->helperText('Hostname oder IP-Adresse des Datenbankservers')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. localhost oder db.example.com'),
                Forms\Components\TextInput::make('database_name')
                    ->label('Datenbankname')
                    ->helperText('Name der zu verwendenden Datenbank auf dem Server')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. game_db'),
                Forms\Components\TextInput::make('database_user')
                    ->label('Datenbank-Benutzer')
                    ->helperText('Benutzername für den Datenbankzugriff')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. db_user'),
                Forms\Components\TextInput::make('database_password')
                    ->label('Datenbank-Passwort')
                    ->helperText('Sicheres Passwort für den Datenbankzugriff')
                    ->password()
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('server_name')
                    ->label('Serverbezeichnung')
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
            'index' => Pages\ListServerInstances::route('/'),
            'create' => Pages\CreateServerInstance::route('/create'),
            'edit' => Pages\EditServerInstance::route('/{record}/edit'),
        ];
    }
}
