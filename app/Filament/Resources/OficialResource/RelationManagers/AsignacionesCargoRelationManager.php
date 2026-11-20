<?php

namespace App\Filament\Resources\OficialResource\RelationManagers;

use App\Models\AsignacionCargoOficial;
use App\Services\Oficiales\GuardarAsignacionCargo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AsignacionesCargoRelationManager extends RelationManager
{
    protected static string $relationship = 'asignacionesCargo';

    protected static ?string $title = 'Historial de cargos';

    protected static ?string $modelLabel = 'Asignación de cargo';

    protected static ?string $pluralModelLabel = 'Asignaciones de cargos';

    public function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Select::make('cargo_id')
                ->label('Cargo institucional')
                ->options(
                    fn(): array => \App\Models\Cargo::query()
                        ->where('activo', true)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id')
                        ->all()
                )
                ->searchable()
                ->required(),


            Forms\Components\Select::make('tipo_asignacion')
                ->label('Tipo de asignación')
                ->options([
                    AsignacionCargoOficial::TIPO_TITULAR => 'Titular',
                    AsignacionCargoOficial::TIPO_TEMPORAL => 'Temporal',
                ])
                ->required(),

            Forms\Components\DatePicker::make('fecha_inicio')
                ->label('Fecha de inicio')
                ->required(),

            Forms\Components\DatePicker::make('fecha_fin')
                ->label('Fecha de finalización'),

            Forms\Components\Textarea::make('observaciones')
                ->label('Observaciones')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cargo.nombre')
            ->columns([
                Tables\Columns\TextColumn::make('cargo.nombre')
                    ->label('Cargo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipo_asignacion')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(
                        fn(string $state) =>
                            $state === AsignacionCargoOficial::TIPO_TITULAR
                            ? 'Titular'
                            : 'Temporal'
                    )
                    ->colors([
                        'success' => AsignacionCargoOficial::TIPO_TITULAR,
                        'info' => AsignacionCargoOficial::TIPO_TEMPORAL,
                    ]),


                Tables\Columns\TextColumn::make('estado_vigencia')
                    ->label('Vigencia')
                    ->getStateUsing(function (AsignacionCargoOficial $record): string {
                        $hoy = today();

                        if ($record->fecha_inicio->gt($hoy)) {
                            return 'programado';
                        }

                        if (
                            $record->fecha_fin !== null &&
                            $record->fecha_fin->lt($hoy)
                        ) {
                            return 'finalizado';
                        }

                        return 'vigente';
                    })
                    ->badge()
                    ->formatStateUsing(
                        fn(string $state): string =>
                            match ($state) {
                                'programado' => 'Programado',
                                'finalizado' => 'Finalizado',
                                default => 'Vigente',
                            }
                    )
                    ->color(
                        fn(string $state): string =>
                            match ($state) {
                                'programado' => 'info',
                                'finalizado' => 'gray',
                                default => 'success',
                            }
                    ),


                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_fin')
                    ->label('Finalización')
                    ->date('d/m/Y')
                    ->placeholder('Vigente'),

                Tables\Columns\TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50),
            ])
            ->defaultSort('fecha_inicio', 'desc')

            ->headerActions([
                Tables\Actions\Action::make('asignarCargo')
                    ->label('Asignar cargo')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->form(fn(): array => [
                        Forms\Components\Select::make('cargo_id')
                            ->label('Cargo institucional')
                            ->relationship(
                                name: 'cargo',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: fn($query) =>
                                    $query->where('activo', true)
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('tipo_asignacion')
                            ->label('Tipo de asignación')
                            ->options([
                                AsignacionCargoOficial::TIPO_TITULAR => 'Titular',
                                AsignacionCargoOficial::TIPO_TEMPORAL => 'Temporal',
                            ])
                            ->live()
                            ->required(),

                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha de inicio')
                            ->required(),


                        Forms\Components\DatePicker::make('fecha_fin')
                            ->label('Fecha de finalización')
                            ->required(
                                fn(Forms\Get $get): bool =>
                                    $get('tipo_asignacion') ===
                                    AsignacionCargoOficial::TIPO_TEMPORAL
                            )
                            ->afterOrEqual('fecha_inicio'),


                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3),
                    ])


                    ->action(function (array $data): void {
                        $oficial = $this->getOwnerRecord();

                        try {
                            app(GuardarAsignacionCargo::class)->crear([
                                ...$data,
                                'oficial_id' => $oficial->getKey(),
                            ]);

                            Notification::make()
                                ->title('Cargo asignado correctamente')
                                ->success()
                                ->send();

                        } catch (\Illuminate\Validation\ValidationException $e) {

                            Notification::make()
                                ->title('No se pudo asignar el cargo')
                                ->body(
                                    collect($e->errors())
                                        ->flatten()
                                        ->implode("\n")
                                )
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }
                    })

                ,
            ])



            ->actions([


                Tables\Actions\Action::make('finalizarCargo')
                    ->label('Finalizar')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->visible(
                        fn(AsignacionCargoOficial $record): bool =>
                            $record->tipo_asignacion ===
                            AsignacionCargoOficial::TIPO_TITULAR
                            && $record->fecha_fin === null
                            && $record->fecha_inicio->lte(today())
                    )
                    ->modalHeading('Finalizar cargo titular')
                    ->modalDescription(
                        'Esta acción finalizará el nombramiento sin eliminar su historial.'
                    )
                    ->form([
                        Forms\Components\DatePicker::make('fecha_fin')
                            ->label('Fecha de finalización')
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (AsignacionCargoOficial $record, array $data): void {
                        try {
                            app(GuardarAsignacionCargo::class)->finalizar(
                                $record,
                                $data['fecha_fin']
                            );

                            Notification::make()
                                ->title('Cargo finalizado correctamente')
                                ->success()
                                ->send();

                        } catch (\Illuminate\Validation\ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo finalizar el cargo')
                                ->body(
                                    collect($e->errors())
                                        ->flatten()
                                        ->implode("\n")
                                )
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

            ])


            ->bulkActions([]);
    }
}
