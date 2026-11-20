<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OficialResource\Pages;
use App\Filament\Resources\OficialResource\RelationManagers;
use App\Filament\Resources\OficialResource\RelationManagers\AsignacionesCargoRelationManager;
use App\Models\Oficial;
use App\Models\Persona;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OficialResource extends Resource
{

    protected static ?string $model = Oficial::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Oficiales';

    protected static ?string $modelLabel = 'Oficial';

    protected static ?string $pluralModelLabel = 'Oficiales';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del oficial')
                    ->schema([
                        Forms\Components\Select::make('persona_id')
                            ->label('Persona')
                            ->relationship(
                                name: 'persona',
                                titleAttribute: 'catalogo',
                                modifyQueryUsing: fn(Builder $query, ?Oficial $record) =>
                                    $query->where(function (Builder $q) use ($record) {
                                        $q->whereDoesntHave('oficial');
                                        $q->WhereDoesntHave('cadete');

                                        if ($record?->persona_id) {
                                            $q->orWhere(
                                                'id',
                                                $record->persona_id
                                            );
                                        }
                                    })
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn(Persona $record): string =>
                                    "{$record->catalogo} - {$record->nombre_completo}"
                            )
                            ->searchable([
                                'catalogo',
                                'primer_nombre',
                                'segundo_nombre',
                                'primer_apellido',
                                'segundo_apellido',
                            ])
                            ->preload()
                            ->required()
                            ->disabledOn('edit'),

                        Forms\Components\Select::make('grado_id')
                            ->label('Grado militar')
                            ->relationship(
                                name: 'grado',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: fn(Builder $query) =>
                                    $query->where('activo', true)
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'activo' => 'Activo',
                                'inactivo' => 'Inactivo',
                            ])
                            ->default('activo')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('persona.foto')
                    ->label('Fotografía')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('persona.catalogo')
                    ->label('Catálogo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('persona.nombre_completo')
                    ->label('Nombre completo')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('persona', function (Builder $q) use ($search) {
                            $q->where(function (Builder $nombres) use ($search) {
                                $nombres
                                    ->where('primer_nombre', 'like', "%{$search}%")
                                    ->orWhere('segundo_nombre', 'like', "%{$search}%")
                                    ->orWhere('primer_apellido', 'like', "%{$search}%")
                                    ->orWhere('segundo_apellido', 'like', "%{$search}%");
                            });
                        });
                    }),

                Tables\Columns\TextColumn::make('grado.nombre')
                    ->label('Grado')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'success' => 'activo',
                        'danger' => 'inactivo',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }


    public static function getRelations(): array
    {
        return [
            AsignacionesCargoRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOficials::route('/'),
            'create' => Pages\CreateOficial::route('/create'),
            'edit' => Pages\EditOficial::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
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
