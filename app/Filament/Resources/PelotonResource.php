<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelotonResource\Pages;
use App\Models\Peloton;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PelotonResource extends Resource
{
    protected static ?string $model = Peloton::class;

    protected static ?string $navigationIcon =
        'heroicon-o-user-group';

    protected static ?string $navigationGroup =
        'Gestión de cadetes';

    protected static ?string $navigationLabel =
        'Pelotones';

    protected static ?string $modelLabel =
        'Pelotón';

    protected static ?string $pluralModelLabel =
        'Pelotones';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('pelotones.ver') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('pelotones.crear') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('pelotones.editar') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('pelotones.eliminar') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre del pelotón')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Forms\Components\Toggle::make('activo')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Pelotón')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPelotons::route('/'),
            'create' => Pages\CreatePeloton::route('/create'),
            'edit' => Pages\EditPeloton::route('/{record}/edit'),
        ];
    }
}
