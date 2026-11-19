<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CadeteResource\Pages;
use App\Filament\Resources\CadeteResource\RelationManagers;
use App\Models\Cadete;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CadeteResource\RelationManagers\AsignacionesPelotonRelationManager;

class CadeteResource extends Resource
{
    protected static ?string $model = Cadete::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Gestión de cadetes';

    protected static ?string $navigationLabel = 'Cadetes';

    protected static ?string $modelLabel = 'Cadete';

    protected static ?string $pluralModelLabel = 'Cadetes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificación personal')
                    ->schema([

                        Forms\Components\FileUpload::make('foto')
                            ->label('Fotografía')
                            ->image()
                            ->avatar()
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                            ])
                            ->maxSize(2048)
                            ->disk('public')
                            ->directory('personas/fotos')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->imagePreviewHeight('180')
                            ->columnSpanFull()
                            ->alignCenter()
                        ,
                        Forms\Components\TextInput::make('catalogo')
                            ->label('Catálogo')
                            ->required()
                            ->unique(
                                table: 'personas',
                                column: 'catalogo',
                                ignorable: fn(?Cadete $record) => $record?->persona
                            )
                            ->maxLength(50)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('primer_nombre')
                            ->label('Primer nombre')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('segundo_nombre')
                            ->label('Segundo nombre')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('primer_apellido')
                            ->label('Primer apellido')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('segundo_apellido')
                            ->label('Segundo apellido')
                            ->maxLength(100),

                        Forms\Components\Select::make('tipo_documento')
                            ->label('Tipo de documento')
                            ->options([
                                'DPI' => 'DPI',
                                'PASAPORTE' => 'Pasaporte',
                                'OTRO' => 'Otro',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('numero_documento')
                            ->label('Número de documento')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('nit')
                            ->label('NIT')
                            ->maxLength(30),

                        Forms\Components\DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de nacimiento')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información personal y contacto')
                    ->schema([
                        Forms\Components\Select::make('etnia')
                            ->label('Etnia')
                            ->options([
                                'Maya' => 'Maya',
                                'Garífuna' => 'Garífuna',
                                'Xinka' => 'Xinka',
                                'Ladino/Mestizo' => 'Ladino/Mestizo',
                            ])
                            ->native(false)
                            ->searchable(),

                        Forms\Components\Select::make('grupo_sanguineo')
                            ->label('Grupo sanguíneo')
                            ->options([
                                'A+' => 'A+',
                                'A-' => 'A-',
                                'B+' => 'B+',
                                'B-' => 'B-',
                                'AB+' => 'AB+',
                                'AB-' => 'AB-',
                                'O+' => 'O+',
                                'O-' => 'O-',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(30),

                        Forms\Components\TextInput::make('correo_electronico')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('direccion')
                            ->label('Dirección')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información institucional')
                    ->schema([
                        Forms\Components\Select::make('grado_id')
                            ->label('Grado')
                            ->relationship(
                                name: 'grado',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: fn($query) =>
                                    $query->where('activo', true)
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('promocion_id')
                            ->label('Promoción')
                            ->relationship(
                                name: 'promocion',
                                titleAttribute: 'numero'
                            )
                            ->searchable()
                            ->preload(),

                        Forms\Components\DatePicker::make('fecha_ingreso')
                            ->label('Fecha de ingreso')
                            ->native(false),

                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'activo' => 'Activo',
                            ])
                            ->default('activo')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query->with([
                    'persona',
                    'grado',
                    'promocion',
                    'asignacionActual.semestre',
                ])
            )
            ->columns([
                Tables\Columns\ImageColumn::make('persona.foto')
                    ->label('Fotografía')
                    ->disk('public')
                    ->circular()
                    ->size(50)
                ,
                Tables\Columns\TextColumn::make('persona.catalogo')
                    ->label('Catálogo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('persona.primer_nombre')
                    ->label('Nombre completo')
                    ->formatStateUsing(
                        fn(Cadete $record) =>
                            $record->persona?->nombre_completo ?? 'Sin nombre'
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('persona', function (Builder $personaQuery) use ($search) {
                            $personaQuery->where(function (Builder $q) use ($search) {
                                $q->where('primer_nombre', 'like', "%{$search}%")
                                    ->orWhere('segundo_nombre', 'like', "%{$search}%")
                                    ->orWhere('primer_apellido', 'like', "%{$search}%")
                                    ->orWhere('segundo_apellido', 'like', "%{$search}%");
                            });
                        });
                    }),

                Tables\Columns\TextColumn::make('grado.nombre')
                    ->label('Grado')
                    ->sortable(),

                Tables\Columns\TextColumn::make('promocion.numero')
                    ->label('Promoción')
                    ->sortable(),

                Tables\Columns\TextColumn::make('semestre_actual')
                    ->label('Semestre actual')
                    ->getStateUsing(
                        fn(Cadete $record): string =>
                            $record->asignacionActual?->semestre?->nombre
                            ?? 'Sin asignación'
                    )
                    ->badge()
                    ->color(
                        fn(string $state): string =>
                            $state === 'Sin asignación' ? 'gray' : 'info'
                    ),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(
                        fn(string $state): string => ucfirst($state)
                    )
                    ->color(
                        fn(string $state): string => match ($state) {
                            'activo' => 'success',
                            default => 'gray',
                        }
                    ),

                Tables\Columns\TextColumn::make('fecha_ingreso')
                    ->label('Fecha de ingreso')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grado_id')
                    ->label('Grado')
                    ->relationship('grado', 'nombre'),

                Tables\Filters\SelectFilter::make('promocion_id')
                    ->label('Promoción')
                    ->relationship('promocion', 'numero'),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AsignacionesSemestreRelationManager::class,
            AsignacionesPelotonRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCadetes::route('/'),
            'create' => Pages\CreateCadete::route('/create'),
            'edit' => Pages\EditCadete::route('/{record}/edit'),
        ];
    }
}
