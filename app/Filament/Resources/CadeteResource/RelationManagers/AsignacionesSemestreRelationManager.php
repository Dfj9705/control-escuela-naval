<?php

namespace App\Filament\Resources\CadeteResource\RelationManagers;

use App\Filament\Resources\CadeteResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\ValidationException;

class AsignacionesSemestreRelationManager extends RelationManager
{
    protected static string $relationship = 'asignacionesSemestre';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return CadeteResource::canEdit($ownerRecord);
    }

    public function mount(): void
    {
        abort_unless(static::canViewForRecord($this->getOwnerRecord(), $this->getPageClass()), 403);

        parent::mount();
    }

    public function hydrate(): void
    {
        abort_unless(static::canViewForRecord($this->getOwnerRecord(), $this->getPageClass()), 403);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('periodo_academico_id')
                    ->label('Período académico')
                    ->options(
                        \App\Models\PeriodoAcademico::query()
                            ->orderByDesc('anio')
                            ->orderByDesc('numero')
                            ->get()
                            ->mapWithKeys(fn($periodo) => [
                                $periodo->id => "{$periodo->anio}-{$periodo->numero}",
                            ])
                            ->all()
                    )
                    ->required()
                    ->searchable()
                    ->native(false),

                Forms\Components\Select::make('semestre_id')
                    ->label('Semestre')
                    ->relationship(
                        name: 'semestre',
                        titleAttribute: 'nombre',
                        modifyQueryUsing: fn(Builder $query) =>
                            $query->where('activo', true)
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->columns(2);
    }

    protected function canCreate(): bool
    {
        return CadeteResource::canEdit($this->getOwnerRecord())
            && (auth()->user()?->can('asignaciones.crear') ?? false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('periodoAcademico.anio')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('periodoAcademico.numero')
                    ->label('Período')
                    ->formatStateUsing(
                        fn($state) => match ((int) $state) {
                            1 => 'Primer período',
                            2 => 'Segundo período',
                            default => $state,
                        }
                    ),

                Tables\Columns\TextColumn::make('semestre.nombre')
                    ->label('Semestre'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Asignar semestre')
                    ->mutateFormDataUsing(function (array $data): array {

                        $existe = \App\Models\AsignacionSemestre::query()
                            ->where('cadete_id', $this->getOwnerRecord()->getKey())
                            ->where('periodo_academico_id', $data['periodo_academico_id'])
                            ->exists();

                        if ($existe) {
                            throw ValidationException::withMessages([
                                'mountedTableActionsData.0.periodo_academico_id' =>
                                    'Este cadete ya tiene una asignación en el período seleccionado.',
                            ]);
                        }

                        return $data;
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
