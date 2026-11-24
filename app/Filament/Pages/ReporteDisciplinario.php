<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\PeriodoAcademico;
use App\Models\Semestre;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\AsignacionSemestre;
use App\Models\SancionDisciplinaria;
use Illuminate\Database\Eloquent\Builder;
use App\Models\MeritoDisciplinario;
use App\Services\CalcularConductaMensual;

class ReporteDisciplinario extends Page
{
    public ?array $data = [];
    public string $reporteActivo = 'general';
    public ?int $cadeteDetalleId = null;
    protected static ?string $navigationIcon =
        'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup =
        'Reportes';

    protected static ?string $navigationLabel =
        'Reporte disciplinario';

    protected static ?string $title = 'Reportes disciplinarios';

    protected static ?int $navigationSort = 1;

    protected static string $view =
        'filament.pages.reporte-disciplinario';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null
            && (
                $user->hasRole('superadministrador')
                || $user->can('reportes.ver')
            );
    }


    public function mount(): void
    {
        $this->form->fill([
            'periodo_academico_id' => PeriodoAcademico::query()
                ->where('activo', true)
                ->orderByDesc('anio')
                ->orderByDesc('numero')
                ->value('id'),

            'semestre_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('periodo_academico_id')
                    ->label('Período académico')
                    ->options(
                        PeriodoAcademico::query()
                            ->orderByDesc('anio')
                            ->orderByDesc('numero')
                            ->get()
                            ->mapWithKeys(fn(PeriodoAcademico $periodo) => [
                                $periodo->id =>
                                    $periodo->anio . ' - Período ' . $periodo->numero,
                            ])
                            ->all()
                    )
                    ->required()
                    ->native(false)
                    ->live(),

                Forms\Components\Select::make('semestre_id')
                    ->label('Semestre')
                    ->options(
                        Semestre::query()
                            ->orderBy('numero')
                            ->pluck('nombre', 'id')
                            ->all()
                    )
                    ->placeholder('Todos los semestres')
                    ->native(false)
                    ->live(),
            ])
            ->columns(2)
            ->statePath('data');
    }


    public function obtenerResultados(): array
    {
        $periodoId = $this->data['periodo_academico_id'] ?? null;
        $semestreId = $this->data['semestre_id'] ?? null;

        if (!$periodoId) {
            return [];
        }

        $periodo = PeriodoAcademico::find($periodoId);

        if (!$periodo) {
            return [];
        }

        $asignaciones = AsignacionSemestre::query()
            ->with([
                'cadete.persona',
                'cadete.grado',
                'semestre',
            ])
            ->where('periodo_academico_id', $periodo->id)
            ->when(
                filled($semestreId),
                fn(Builder $query) =>
                    $query->where('semestre_id', $semestreId)
            )
            ->orderBy('semestre_id')
            ->get();

        if ($asignaciones->isEmpty()) {
            return [];
        }



        $totales = SancionDisciplinaria::query()
            ->select('cadete_id')
            ->selectRaw('COUNT(*) AS total_faltas')
            ->selectRaw(
                "COALESCE(SUM(CASE
                WHEN tipo_sancion = ? THEN cantidad_aplicada
                ELSE 0
            END), 0) AS total_horas",
                [SancionDisciplinaria::TIPO_HORAS]
            )
            ->selectRaw(
                "COALESCE(SUM(CASE
                WHEN tipo_sancion = ? THEN cantidad_aplicada
                ELSE 0
            END), 0) AS total_demeritos",
                [SancionDisciplinaria::TIPO_DEMERITOS]
            )
            ->whereIn(
                'cadete_id',
                $asignaciones->pluck('cadete_id')
            )
            ->where(
                'estado',
                SancionDisciplinaria::ESTADO_CONFIRMADA
            )
            ->whereBetween('fecha_falta', [
                $periodo->fecha_inicio->toDateString(),
                $periodo->fecha_fin->toDateString(),
            ])
            ->groupBy('cadete_id')
            ->get()
            ->keyBy('cadete_id');


        $meritos = MeritoDisciplinario::query()
            ->select('cadete_id')
            ->selectRaw('SUM(cantidad) AS total_meritos')
            ->whereIn(
                'cadete_id',
                $asignaciones->pluck('cadete_id')
            )
            ->where(
                'estado',
                MeritoDisciplinario::ESTADO_VIGENTE
            )
            ->whereBetween('fecha_merito', [
                $periodo->fecha_inicio->toDateString(),
                $periodo->fecha_fin->toDateString(),
            ])
            ->groupBy('cadete_id')
            ->pluck('total_meritos', 'cadete_id');


        return $asignaciones
            ->map(function (AsignacionSemestre $asignacion) use ($totales, $meritos) {
                $cadete = $asignacion->cadete;
                $total = $totales->get($asignacion->cadete_id);
                $demeritos = (int) ($total?->total_demeritos ?? 0);
                $limite = $asignacion->semestre?->limiteDemeritos();

                $porcentaje = $limite > 0
                    ? round(($demeritos / $limite) * 100, 2)
                    : null;
                return [
                    'cadete_id' => $cadete->id,
                    'catalogo' => $cadete->persona?->catalogo ?? '',
                    'nombre' => $cadete->persona?->nombre_completo ?? '',
                    'grado' => $cadete->grado?->nombre ?? '',
                    'semestre' => $asignacion->semestre?->nombre ?? '',
                    'faltas' => (int) ($total?->total_faltas ?? 0),
                    'horas' => (int) ($total?->total_horas ?? 0),
                    'demeritos' => $demeritos,
                    'limite' => $limite,
                    'porcentaje' => $porcentaje,
                    'meritos' => (int) ($meritos->get($cadete->id) ?? 0),
                ];
            })
            ->all();
    }


    public function obtenerResumen(array $resultados): array
    {
        return [
            'cadetes' => count($resultados),

            'faltas' => array_sum(
                array_column($resultados, 'faltas')
            ),

            'horas' => array_sum(
                array_column($resultados, 'horas')
            ),

            'demeritos' => array_sum(
                array_column($resultados, 'demeritos')
            ),

            'cadetes_con_demeritos' => count(
                array_filter(
                    $resultados,
                    fn(array $fila) => $fila['demeritos'] > 0
                )
            ),

            'meritos' => array_sum(
                array_column($resultados, 'meritos')
            ),
        ];
    }



    public function obtenerDetalleSanciones(int $cadeteId): array
    {
        $periodoId = $this->data['periodo_academico_id'] ?? null;

        if (!$periodoId) {
            return [];
        }

        $periodo = PeriodoAcademico::find($periodoId);

        if (!$periodo) {
            return [];
        }

        // Verificar que el cadete pertenezca al período
        // y al semestre seleccionado.
        $pertenece = AsignacionSemestre::query()
            ->where('cadete_id', $cadeteId)
            ->where('periodo_academico_id', $periodo->id)
            ->when(
                filled($this->data['semestre_id'] ?? null),
                fn(Builder $query) => $query->where(
                    'semestre_id',
                    $this->data['semestre_id']
                )
            )
            ->exists();

        if (!$pertenece) {
            return [];
        }

        return SancionDisciplinaria::query()
            ->with('faltaDisciplinaria')
            ->where('cadete_id', $cadeteId)
            ->where(
                'estado',
                SancionDisciplinaria::ESTADO_CONFIRMADA
            )
            ->whereBetween('fecha_falta', [
                $periodo->fecha_inicio->toDateString(),
                $periodo->fecha_fin->toDateString(),
            ])
            ->orderBy('fecha_falta')
            ->orderBy('id')
            ->get()
            ->map(fn(SancionDisciplinaria $sancion) => [
                'fecha' => $sancion->fecha_falta?->format('d/m/Y'),
                'falta' => $sancion->faltaDisciplinaria?->descripcion ?? '',
                'tipo' => $sancion->tipo_sancion,
                'cantidad' => $sancion->cantidad_aplicada,
                'hechos' => $sancion->descripcion_hechos,
            ])
            ->all();
    }


    public function obtenerDetalleMeritos(int $cadeteId): array
    {
        $periodoId = $this->data['periodo_academico_id'] ?? null;

        if (!$periodoId) {
            return [];
        }

        $periodo = PeriodoAcademico::find($periodoId);

        if (!$periodo) {
            return [];
        }

        // Verificar que el cadete pertenezca al período
        // y al semestre seleccionado.
        $pertenece = AsignacionSemestre::query()
            ->where('cadete_id', $cadeteId)
            ->where('periodo_academico_id', $periodo->id)
            ->when(
                filled($this->data['semestre_id'] ?? null),
                fn(Builder $query) => $query->where(
                    'semestre_id',
                    $this->data['semestre_id']
                )
            )
            ->exists();

        if (!$pertenece) {
            return [];
        }

        return MeritoDisciplinario::query()
            ->with('responsable')
            ->where('cadete_id', $cadeteId)
            ->where(
                'estado',
                MeritoDisciplinario::ESTADO_VIGENTE
            )
            ->whereBetween('fecha_merito', [
                $periodo->fecha_inicio->toDateString(),
                $periodo->fecha_fin->toDateString(),
            ])
            ->orderBy('fecha_merito')
            ->orderBy('id')
            ->get()
            ->map(fn(MeritoDisciplinario $merito) => [
                'fecha' => $merito->fecha_merito?->format('d/m/Y'),
                'cantidad' => $merito->cantidad,
                'motivo' => $merito->motivo,
                'responsable' => $merito->responsable?->nombre_completo ?? '—',
            ])
            ->all();
    }



    public function seleccionarCadete(int $cadeteId): void
    {
        $this->cadeteDetalleId =
            $this->cadeteDetalleId === $cadeteId
            ? null
            : $cadeteId;
    }

    public function cambiarReporte(string $reporte): void
    {
        if (!in_array($reporte, ['general', 'conducta'], true)) {
            return;
        }

        $this->reporteActivo = $reporte;
        $this->cadeteDetalleId = null;
    }


    public function obtenerMesesPeriodo(): array
    {
        $periodo = PeriodoAcademico::find(
            $this->data['periodo_academico_id'] ?? null
        );

        if (!$periodo) {
            return [];
        }


        $meses = [];

        $fecha = $periodo->fecha_inicio
            ->copy()
            ->startOfMonth();

        $fin = $periodo->fecha_fin
            ->copy()
            ->startOfMonth();

        while ($fecha->lte($fin)) {
            $meses[] = [
                'clave' => $fecha->format('Y-m'),
                'anio' => $fecha->year,
                'mes' => $fecha->month,
                'nombre' => ucfirst(
                    $fecha->locale('es')->translatedFormat('F')
                ),
                'futuro' => $fecha->gt(now()),
            ];

            $fecha->addMonth();
        }

        return $meses;
    }


    public function obtenerCalificacionesConducta(): array
    {
        $periodo = PeriodoAcademico::find(
            $this->data['periodo_academico_id'] ?? null
        );

        if (!$periodo) {
            return [];
        }

        $meses = $this->obtenerMesesPeriodo();

        $asignaciones = AsignacionSemestre::query()
            ->with([
                'cadete.persona',
                'cadete.grado',
                'semestre',
            ])
            ->where('periodo_academico_id', $periodo->id)
            ->when(
                filled($this->data['semestre_id'] ?? null),
                fn(Builder $query) => $query->where(
                    'semestre_id',
                    $this->data['semestre_id']
                )
            )
            ->get();

        if ($asignaciones->isEmpty()) {
            return [];
        }

        $cadeteIds = $asignaciones->pluck('cadete_id');

        $inicio = $periodo->fecha_inicio->toDateString();
        $fin = $periodo->fecha_fin->toDateString();

        // Agrupar sanciones por cadete y mes.
        $sanciones = SancionDisciplinaria::query()
            ->select('cadete_id')
            ->selectRaw(
                "DATE_FORMAT(fecha_falta, '%Y-%m') AS mes"
            )
            ->selectRaw(
                'SUM(CASE WHEN tipo_sancion = ?
                THEN cantidad_aplicada ELSE 0 END) AS horas',
                [SancionDisciplinaria::TIPO_HORAS]
            )
            ->selectRaw(
                'SUM(CASE WHEN tipo_sancion = ?
                THEN cantidad_aplicada ELSE 0 END) AS demeritos',
                [SancionDisciplinaria::TIPO_DEMERITOS]
            )
            ->whereIn('cadete_id', $cadeteIds)
            ->where(
                'estado',
                SancionDisciplinaria::ESTADO_CONFIRMADA
            )
            ->whereBetween('fecha_falta', [$inicio, $fin])
            ->groupBy('cadete_id', 'mes')
            ->get()
            ->keyBy(
                fn($registro) =>
                    $registro->cadete_id . '-' . $registro->mes
            );

        // Agrupar méritos vigentes por cadete y mes.
        $meritos = MeritoDisciplinario::query()
            ->select('cadete_id')
            ->selectRaw(
                "DATE_FORMAT(fecha_merito, '%Y-%m') AS mes"
            )
            ->selectRaw('SUM(cantidad) AS total')
            ->whereIn('cadete_id', $cadeteIds)
            ->where(
                'estado',
                MeritoDisciplinario::ESTADO_VIGENTE
            )
            ->whereBetween('fecha_merito', [$inicio, $fin])
            ->groupBy('cadete_id', 'mes')
            ->get()
            ->keyBy(
                fn($registro) =>
                    $registro->cadete_id . '-' . $registro->mes
            );

        $calculador = app(CalcularConductaMensual::class);

        return $asignaciones
            ->map(function (AsignacionSemestre $asignacion) use ($meses, $sanciones, $meritos, $calculador) {
                $cadete = $asignacion->cadete;

                $calificaciones = [];

                foreach ($meses as $mes) {
                    $clave = $cadete->id . '-' . $mes['clave'];

                    if ($mes['futuro']) {
                        $calificaciones[$mes['clave']] = null;

                        continue;
                    }

                    $sancion = $sanciones->get($clave);
                    $merito = $meritos->get($clave);

                    $calificaciones[$mes['clave']] =
                        $calculador->calcularDesdeTotales(
                            (int) ($sancion?->horas ?? 0),
                            (int) ($sancion?->demeritos ?? 0),
                            (int) ($merito?->total ?? 0)
                        );
                }

                $notas = collect($calificaciones)
                    ->filter(fn($resultado) => $resultado !== null)
                    ->pluck('nota');

                return [
                    'cadete_id' => $cadete->id,
                    'catalogo' => $cadete->persona?->catalogo ?? '',
                    'nombre' => $cadete->persona?->nombre_completo ?? '',
                    'grado' => $cadete->grado?->nombre ?? '',
                    'semestre' => $asignacion->semestre?->nombre ?? '',
                    'semestre_numero' => $asignacion->semestre?->numero,
                    'calificaciones' => $calificaciones,
                    'promedio' => $notas->isNotEmpty()
                        ? round($notas->avg(), 2)
                        : null,
                ];
            })
            ->sortBy([
                ['semestre_numero', 'desc'],
                ['nombre', 'asc'],
            ])
            ->values()
            ->all();
    }


}
