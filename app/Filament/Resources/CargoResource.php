<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CargoResource\Pages;
use App\Filament\Resources\CargoResource\RelationManagers;
use App\Models\Cargo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CargoResource extends Resource
{

    protected static ?string $model = Cargo::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Cargos institucionales';

    protected static ?string $modelLabel = 'Cargo institucional';

    protected static ?string $pluralModelLabel = 'Cargos institucionales';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del cargo')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre del cargo')
                            ->required()
                            ->maxLength(150)
                            ->unique(
                                table: 'cargos',
                                column: 'nombre',
                                ignoreRecord: true
                            )
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('activo')
                            ->label('Cargo activo')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Cargo institucional')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('nombre');
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
            'index' => Pages\ListCargos::route('/'),
            'create' => Pages\CreateCargo::route('/create'),
            'edit' => Pages\EditCargo::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
