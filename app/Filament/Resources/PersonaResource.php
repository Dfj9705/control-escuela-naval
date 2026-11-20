<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonaResource\Pages;
use App\Filament\Resources\PersonaResource\RelationManagers;
use App\Models\Persona;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonaResource extends Resource
{
    protected static ?string $model = Persona::class;

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Personas';

    protected static ?string $modelLabel = 'Persona';

    protected static ?string $pluralModelLabel = 'Personas';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 2;


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
                            ->alignCenter(),

                        Forms\Components\TextInput::make('catalogo')
                            ->label('Catálogo')
                            ->required()
                            ->unique(
                                table: 'personas',
                                column: 'catalogo',
                                ignoreRecord: true
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
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query->with([
                    'cadete',
                    'usuario',
                ])
            )
            ->columns([
                Tables\Columns\ImageColumn::make('foto')
                    ->label('Fotografía')
                    ->disk('public')
                    ->circular()
                    ->size(45),

                Tables\Columns\TextColumn::make('catalogo')
                    ->label('Catálogo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('primer_nombre')
                    ->label('Nombre completo')
                    ->formatStateUsing(
                        fn(Persona $record): string =>
                            $record->nombre_completo
                    )
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->where(function (Builder $query) use ($search) {
                                $query->where('primer_nombre', 'like', "%{$search}%")
                                    ->orWhere('segundo_nombre', 'like', "%{$search}%")
                                    ->orWhere('primer_apellido', 'like', "%{$search}%")
                                    ->orWhere('segundo_apellido', 'like', "%{$search}%");
                            });
                        }
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_documento')
                    ->label('Documento')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('correo_electronico')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\IconColumn::make('tiene_cadete')
                    ->label('Cadete')
                    ->boolean()
                    ->getStateUsing(
                        fn(Persona $record): bool =>
                            $record->cadete !== null
                    ),
                Tables\Columns\IconColumn::make('tiene_oficial')
                    ->label('Oficial')
                    ->boolean()
                    ->getStateUsing(
                        fn(Persona $record): bool =>
                            $record->oficial !== null
                    ),

                Tables\Columns\IconColumn::make('tiene_usuario')
                    ->label('Usuario')
                    ->boolean()
                    ->getStateUsing(
                        fn(Persona $record): bool =>
                            $record->usuario !== null
                    ),
            ])
            ->defaultSort('catalogo')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No hay personas registradas')
            ->emptyStateDescription(
                'Registra personas para vincularlas con cadetes, oficiales o usuarios.'
            )
            ->emptyStateIcon('heroicon-o-identification');
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
            'index' => Pages\ListPersonas::route('/'),
            'create' => Pages\CreatePersona::route('/create'),
            'edit' => Pages\EditPersona::route('/{record}/edit'),
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
