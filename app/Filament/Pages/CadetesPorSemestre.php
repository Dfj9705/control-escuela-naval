<?php

namespace App\Filament\Pages;

use App\Models\AsignacionSemestre;
use App\Models\PeriodoAcademico;
use App\Models\Semestre;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;

class CadetesPorSemestre extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Organización académica';

    protected static ?string $navigationLabel = 'Cadetes por semestre';

    protected static ?string $title = 'Cadetes por semestre';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.cadetes-por-semestre';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AsignacionSemestre::query()
                    ->with([
                        'cadete.persona',
                        'cadete.grado',
                        'cadete.promocion',
                        'semestre',
                        'periodoAcademico',
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('cadete.persona.catalogo')
                    ->label('Catálogo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cadete.persona.nombre_completo')
                    ->label('Nombre completo')
                    ->getStateUsing(
                        fn(AsignacionSemestre $record): string =>
                            $record->cadete?->persona?->nombre_completo ?? ''
                    ),

                Tables\Columns\TextColumn::make('cadete.grado.abreviatura')
                    ->label('Grado'),

                Tables\Columns\TextColumn::make('cadete.promocion.numero')
                    ->label('Promoción'),

                Tables\Columns\TextColumn::make('semestre.nombre')
                    ->label('Semestre')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('periodoAcademico.anio')
                    ->label('Año'),

                Tables\Columns\TextColumn::make('periodoAcademico.numero')
                    ->label('Período')
                    ->formatStateUsing(
                        fn($state): string =>
                            (int) $state === 1
                            ? 'Primero'
                            : 'Segundo'
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('periodo_academico_id')
                    ->label('Período académico')
                    ->options(
                        fn(): array =>
                            PeriodoAcademico::query()
                                ->orderByDesc('anio')
                                ->orderByDesc('numero')
                                ->get()
                                ->mapWithKeys(
                                    fn(PeriodoAcademico $periodo): array => [
                                        $periodo->id =>
                                            "{$periodo->anio} - "
                                            . ($periodo->numero === 1 ? 'I' : 'II'),
                                    ]
                                )
                                ->all()
                    )
                    ->default(
                        fn() => PeriodoAcademico::query()
                            ->where('activo', true)
                            ->value('id')
                    ),

                Tables\Filters\SelectFilter::make('semestre_id')
                    ->label('Semestre')
                    ->relationship('semestre', 'nombre')
                    ->preload(),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function getResumenSemestres(): array
    {
        $filtros = $this->tableFilters ?? [];

        $periodoId = data_get(
            $filtros,
            'periodo_academico_id.value'
        );

        if (blank($periodoId)) {
            return [
                'total' => 0,
                'semestres' => [],
            ];
        }

        $asignaciones = AsignacionSemestre::query()
            ->selectRaw('semestre_id, COUNT(*) as total')
            ->where('periodo_academico_id', $periodoId)
            ->groupBy('semestre_id')
            ->pluck('total', 'semestre_id');

        $semestres = Semestre::query()
            ->whereIn('id', $asignaciones->keys())
            ->orderBy('numero')
            ->get()
            ->map(fn(Semestre $semestre): array => [
                'nombre' => $semestre->nombre,
                'total' => (int) $asignaciones->get($semestre->id, 0),
            ])
            ->all();

        return [
            'total' => array_sum(array_column($semestres, 'total')),
            'semestres' => $semestres,
        ];
    }
}
