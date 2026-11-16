<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Permission;
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Roles y permisos';

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del rol')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del rol')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: 'roles',
                                column: 'name',
                                ignoreRecord: true,
                            )
                            ->disabled(
                                fn(?Role $record) =>
                                    $record?->name === 'superadministrador'
                            ),

                        Forms\Components\Hidden::make('guard_name')
                            ->default('web'),
                    ]),

                Forms\Components\Section::make('Permisos')
                    ->description('Selecciona los permisos que tendrá este rol.')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Permisos asignados')
                            ->relationship('permissions', 'name')
                            ->options(
                                fn() => Permission::query()
                                    ->where('guard_name', 'web')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->columns(3)
                            ->bulkToggleable()
                            ->disabled(
                                fn(?Role $record) =>
                                    $record?->name === 'superadministrador'
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rol')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permisos')
                    ->counts('permissions')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
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
                        fn(Role $record): bool =>
                            static::canEdit($record)
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->visible(
                        fn(Role $record): bool =>
                            static::canDelete($record)
                    ),
            ])
            ->bulkActions([
            ])
            ->emptyStateHeading('No hay roles registrados')
            ->emptyStateDescription(
                'Crea un rol para comenzar a administrar los permisos del sistema.'
            )
            ->emptyStateIcon('heroicon-o-shield-check');
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('roles.ver') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('roles.crear') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('roles.editar')
            && $record->name !== 'superadministrador';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('roles.eliminar')
            && $record->name !== 'superadministrador';
    }
}
