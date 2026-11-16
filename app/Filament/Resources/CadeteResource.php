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

class CadeteResource extends Resource
{
    protected static ?string $model = Cadete::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificación personal')
                    ->schema([
                        Forms\Components\TextInput::make('catalogo')
                            ->label('Número de catálogo')
                            ->required()
                            ->maxLength(30)
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
            ->columns([
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
            'index' => Pages\ListCadetes::route('/'),
            'create' => Pages\CreateCadete::route('/create'),
            'edit' => Pages\EditCadete::route('/{record}/edit'),
        ];
    }
}
