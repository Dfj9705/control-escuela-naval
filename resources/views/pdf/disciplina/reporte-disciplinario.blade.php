
<table class="tabla-encabezado">
    <tr>
        <td class="columna-escudo">

            @if ($escudo)
                <img
                    src="{{ $escudo }}"
                    class="escudo"
                    alt="Escudo Escuela Naval"
                >
            @endif

        </td>

        <td class="columna-institucion">

            <div class="institution">
                ESCUELA NAVAL DE GUATEMALA
            </div>

            <div class="subtitle">
                MARINA DE LA DEFENSA NACIONAL
            </div>

            <div class="subtitle">
                CUERPO DE GUARDIAMARINAS Y CADETES NAVALES
            </div>

        </td>

        <td class="columna-vacia"></td>
    </tr>
</table>

<div class="document-title">
    REPORTE GENERAL DE SANCIONES DISCIPLINARIAS
</div>

<div class="document-description">

    Período académico:
    {{ $periodo->anio }} - Período {{ $periodo->numero }}

    <br>

    Del {{ $periodo->fecha_inicio->format('d/m/Y') }}
    al {{ $periodo->fecha_fin->format('d/m/Y') }}

    <br>

    {{ $semestre ?? 'Todos los semestres' }}

</div>

<table class="tabla-resumen">
    <tr>

        @foreach ([
            'Cadetes' => $resumen['cadetes'],
            'Faltas' => $resumen['faltas'],
            'Horas' => $resumen['horas'],
            'Deméritos' => $resumen['demeritos'],
            'Méritos' => $resumen['meritos'],
            'Con deméritos' => $resumen['cadetes_con_demeritos'],
        ] as $titulo => $valor)

            <td>
                <div class="resumen-label">
                    {{ $titulo }}
                </div>

                <div class="resumen-valor">
                    {{ number_format($valor) }}
                </div>
            </td>

        @endforeach

    </tr>
</table>

<table class="tabla-reporte">

    <thead>
        <tr>
            <th>No.</th>
            <th>Catálogo</th>
            <th>Grado</th>
            <th>Nombre del cadete</th>
            <th>Semestre</th>
            <th>Faltas</th>
            <th>Horas</th>
            <th>Deméritos</th>
            <th>Méritos</th>
            <th>Límite</th>
            <th>% del límite</th>
        </tr>
    </thead>

    <tbody>

        @forelse ($resultados as $fila)

            <tr class="{{ $loop->even ? 'fila-alterna' : '' }}">

                <td class="centro">
                    {{ $loop->iteration }}
                </td>

                <td class="centro">
                    {{ $fila['catalogo'] }}
                </td>

                <td>
                    {{ $fila['grado'] }}
                </td>

                <td>
                    {{ $fila['nombre'] }}
                </td>

                <td>
                    {{ $fila['semestre'] }}
                </td>

                <td class="centro">
                    {{ $fila['faltas'] }}
                </td>

                <td class="centro">
                    {{ $fila['horas'] }}
                </td>

                <td class="centro negrita">
                    {{ $fila['demeritos'] }}
                </td>

                <td class="centro texto-meritos">
                    {{ $fila['meritos'] }}
                </td>

                <td class="centro">
                    {{ $fila['limite'] ?? '—' }}
                </td>

                <td class="centro">
                    {{ $fila['porcentaje'] !== null
                        ? number_format($fila['porcentaje'], 2) . '%'
                        : '—' }}
                </td>

            </tr>

        @empty

            <tr>
                <td colspan="11" class="centro">
                    No existen cadetes asignados al período
                    y semestre seleccionados.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

<div class="nota-reporte">

    El reporte incluye únicamente sanciones confirmadas
    y méritos vigentes durante el período seleccionado.

    <br>

    Los méritos no modifican el porcentaje acumulado
    respecto al límite reglamentario de deméritos.

    <br><br>

    Fecha de generación:
    {{ $fechaGeneracion->format('d/m/Y H:i') }}

</div>
