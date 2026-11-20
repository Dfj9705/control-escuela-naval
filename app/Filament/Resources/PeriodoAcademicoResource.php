<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PeriodoAcademicoResource\Pages;
use App\Filament\Resources\PeriodoAcademicoResource\RelationManagers;
use App\Models\PeriodoAcademico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class PeriodoAcademicoResource extends Resource
{
    protected static ?string $model = PeriodoAcademico::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Organización académica';

    protected static ?string $navigationLabel = 'Períodos académicos';

    protected static ?string $modelLabel = 'Período académico';

    protected static ?string $pluralModelLabel = 'Períodos académicos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del período')
                    ->schema([
                        Forms\Components\TextInput::make('anio')
                            ->label('Año académico')
                            ->numeric()
                            ->integer()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->required(),

                        Forms\Components\Select::make('numero')
                            ->label('Período')
                            ->options([
                                1 => 'Primer período',
                                2 => 'Segundo período',
                            ])
                            ->native(false)
                            ->required()
                            ->unique(
                                table: 'periodos_academicos',
                                column: 'numero',
                                ignoreRecord: true,
                                modifyRuleUsing: fn(Unique $rule, Forms\Get $get): Unique =>
                                    $rule->where('anio', $get('anio'))
                            )
                            ->validationMessages([
                                'unique' => 'Ya existe este período académico para el año seleccionado.',
                            ]),

                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha de inicio')
                            ->native(false),

                        Forms\Components\DatePicker::make('fecha_fin')
                            ->label('Fecha de finalización')
                            ->native(false)
                            ->afterOrEqual('fecha_inicio'),

                        Forms\Components\Toggle::make('activo')
                            ->label('Período activo')
                            ->default(false)
                            ->helperText(
                                'Indica si el período está habilitado para operaciones.'
                            ),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('anio')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero')
                    ->label('Período')
                    ->formatStateUsing(
                        fn($state): string =>
                            (int) $state === 1
                            ? 'Primer período'
                            : 'Segundo período'
                    )
                    ->badge(),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_fin')
                    ->label('Finalización')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('anio', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListPeriodoAcademicos::route('/'),
            'create' => Pages\CreatePeriodoAcademico::route('/create'),
            'edit' => Pages\EditPeriodoAcademico::route('/{record}/edit'),
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
