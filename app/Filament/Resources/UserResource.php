<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del usuario')
                    ->schema([


                        Forms\Components\Select::make('persona_id')
                            ->label('Persona asociada')
                            ->relationship(
                                name: 'persona',
                                titleAttribute: 'catalogo',

                                modifyQueryUsing: fn(Builder $query, ?User $record) =>
                                    $query->where(function (Builder $query) use ($record) {
                                        $query->whereDoesntHave('usuario');

                                        if ($record?->persona_id) {
                                            $query->orWhere('id', $record->persona_id);
                                        }
                                    })

                            )
                            ->getOptionLabelFromRecordUsing(
                                fn($record): string =>
                                    $record->nombre_completo .
                                    ($record->catalogo ? " — {$record->catalogo}" : '')
                            )
                            ->searchable([
                                'catalogo',
                                'primer_nombre',
                                'segundo_nombre',
                                'primer_apellido',
                                'segundo_apellido',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                if (blank($state)) {
                                    return;
                                }

                                $persona = \App\Models\Persona::find($state);

                                if ($persona) {
                                    $set('name', $persona->nombre_completo);
                                }
                            })
                            ->preload()
                            ->nullable()
                            ->helperText('Selecciona la persona a la que pertenece esta cuenta.'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: 'users',
                                column: 'email',
                                ignoreRecord: true
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Seguridad')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->rule(Password::defaults())
                            ->required(
                                fn(string $operation): bool =>
                                    $operation === 'create'
                            )
                            ->dehydrated(
                                fn(?string $state): bool =>
                                    filled($state)
                            )
                            ->helperText(
                                'En edición, deja este campo vacío para conservar la contraseña actual.'
                            ),

                        Forms\Components\Select::make('roles')
                            ->label('Roles asignados')
                            ->multiple()
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn($query) =>
                                    $query->where('guard_name', 'web')
                            )
                            ->disabled(
                                fn(): bool =>
                                    !(auth()->user()?->hasRole('superadministrador') ?? false)
                            )
                            ->preload()
                            ->searchable()
                            ->required()
                            ->helperText(
                                'Los roles determinan los permisos del usuario.'
                            ),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre completo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('persona.nombre_completo')
                    ->label('Persona asociada')
                    ->placeholder('Sin asociar')
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->whereHas('persona', function (Builder $query) use ($search) {
                                $query->where(function (Builder $query) use ($search) {
                                    $query->where('catalogo', 'like', "%{$search}%")
                                        ->orWhere('primer_nombre', 'like', "%{$search}%")
                                        ->orWhere('segundo_nombre', 'like', "%{$search}%")
                                        ->orWhere('primer_apellido', 'like', "%{$search}%")
                                        ->orWhere('segundo_apellido', 'like', "%{$search}%");
                                });
                            });
                        }
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(
                        fn(User $record): bool =>
                            static::canEdit($record)
                    ),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No hay usuarios registrados')
            ->emptyStateDescription(
                'Registra usuarios para administrar el acceso al sistema.'
            )
            ->emptyStateIcon('heroicon-o-users');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
