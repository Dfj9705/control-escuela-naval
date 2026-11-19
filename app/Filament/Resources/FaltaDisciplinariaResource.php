<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaltaDisciplinariaResource\Pages;
use App\Models\FaltaDisciplinaria;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class FaltaDisciplinariaResource extends Resource
{
    protected static ?string $model = FaltaDisciplinaria::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Gestión disciplinaria';

    protected static ?string $navigationLabel = 'Catálogo de faltas';

    protected static ?string $modelLabel = 'Falta disciplinaria';

    protected static ?string $pluralModelLabel = 'Faltas disciplinarias';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Clasificación')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'leve' => 'Leve',
                        'grave' => 'Grave',
                        'gravisima' => 'Gravísima',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'leve' => 'success',
                        'grave' => 'warning',
                        'gravisima' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('numeral')
                    ->label('No.')
                    ->sortable(),

                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoría')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->wrap()
                    ->limit(100),

                Tables\Columns\TextColumn::make('cantidad_base')
                    ->label('Sanción')
                    ->formatStateUsing(
                        fn($state, FaltaDisciplinaria $record): string =>
                            $record->tipo_sancion === 'especial'
                            ? 'Especial'
                            : ($state === null
                                ? 'Sin cantidad'
                                : $state . ' ' . match ($record->tipo_sancion) {
                                    'horas' => 'horas',
                                    'demeritos' => 'deméritos',
                                    default => $record->tipo_sancion,
                                })
                    ),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('version_reglamento')
                    ->label('Reglamento')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Clasificación')
                    ->options([
                        'leve' => 'Leve',
                        'grave' => 'Grave',
                        'gravisima' => 'Gravísima',
                    ]),

                SelectFilter::make('categoria')
                    ->label('Categoría')
                    ->options(
                        fn() => FaltaDisciplinaria::query()
                            ->whereNotNull('categoria')
                            ->distinct()
                            ->orderBy('categoria')
                            ->pluck('categoria', 'categoria')
                            ->toArray()
                    ),

                SelectFilter::make('activo')
                    ->label('Estado')
                    ->options([
                        1 => 'Activo',
                        0 => 'Inactivo',
                    ]),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('tipo');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('version_reglamento', '2026');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaltaDisciplinarias::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('faltas_disciplinarias.ver') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('faltas_disciplinarias.ver') ?? false;
    }
}
