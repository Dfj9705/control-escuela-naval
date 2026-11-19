<?php

namespace App\Filament\Resources\CadeteResource\RelationManagers;

use App\Filament\Resources\SancionDisciplinariaResource;
use App\Models\SancionDisciplinaria;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SancionesDisciplinariasRelationManager extends RelationManager
{
    protected static string $relationship = 'sancionesDisciplinarias';

    protected static ?string $title = 'Historial disciplinario';

    protected static ?string $recordTitleAttribute = 'id';

    public static function canViewForRecord(
        $ownerRecord,
        string $pageClass
    ): bool {
        return auth()->user()?->can(
            'sanciones_disciplinarias.ver'
        ) ?? false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->with(['faltaDisciplinaria', 'responsable'])
            )
            ->defaultSort('fecha_falta', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha_falta')
                    ->label('Fecha de falta')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'faltaDisciplinaria.descripcion'
                )
                    ->label('Falta disciplinaria')
                    ->limit(65)
                    ->tooltip(
                        fn(SancionDisciplinaria $record) =>
                            $record->faltaDisciplinaria?->descripcion
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('tipo_sancion')
                    ->label('Tipo de sanción')
                    ->badge(),

                Tables\Columns\TextColumn::make('cantidad_aplicada')
                    ->label('Cantidad')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        SancionDisciplinaria::ESTADO_CONFIRMADA => 'success',
                        SancionDisciplinaria::ESTADO_ANULADA => 'danger',
                        SancionDisciplinaria::ESTADO_PENDIENTE_FIRMA,
                        SancionDisciplinaria::ESTADO_PENDIENTE_CONFIRMACION => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(
                        fn(string $state): string =>
                            ucfirst(str_replace('_', ' ', $state))
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        SancionDisciplinaria::ESTADO_REGISTRADA => 'Registrada',
                        SancionDisciplinaria::ESTADO_PENDIENTE_FIRMA => 'Pendiente de firma',
                        SancionDisciplinaria::ESTADO_PENDIENTE_CONFIRMACION => 'Pendiente de confirmación',
                        SancionDisciplinaria::ESTADO_CONFIRMADA => 'Confirmada',
                        SancionDisciplinaria::ESTADO_ANULADA => 'Anulada',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('verSancion')
                    ->label('Ver detalle')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(
                        fn(SancionDisciplinaria $record): string =>
                            SancionDisciplinariaResource::getUrl(
                                'view',
                                ['record' => $record]
                            )
                    ),
            ])
            ->bulkActions([]);
    }
}
