
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
    CALIFICACIÓN MENSUAL Y SEMESTRAL DE CONDUCTA
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

@if ($provisional)

    <div class="aviso-provisional">
        REPORTE PROVISIONAL —
        Período académico aún no finalizado.
    </div>

@endif

@forelse ($calificaciones as $nombreSemestre => $cadetes)

    <table class="tabla-reporte">

        <thead>

            <tr>
                <th colspan="{{ count($meses) + 4 }}"
                    style="
                        background-color: #e2e8f0;
                        color: #16324f;
                        text-align: left;
                        font-size: 10pt;
                    ">
                    {{ mb_strtoupper($nombreSemestre) }}
                </th>
            </tr>

            <tr>

                <th style="width: 4%;">No.</th>

                <th>Grado</th>

                <th>Nombre del cadete</th>

                @foreach ($meses as $mes)

                    <th>
                        {{ $mes['nombre'] }}
                    </th>

                @endforeach

                <th>
                    Promedio
                </th>

            </tr>

        </thead>

        <tbody>

            @foreach ($cadetes as $fila)

                <tr class="{{ $loop->even ? 'fila-alterna' : '' }}">

                    <td class="centro">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $fila['grado'] }}
                    </td>

                    <td>
                        {{ $fila['nombre'] }}
                    </td>

                    @foreach ($meses as $mes)

                        @php
                            $resultado = $fila['calificaciones'][
                                $mes['clave']
                            ] ?? null;
                        @endphp

                        <td class="{{ $resultado === null
                            ? 'nota-futura'
                            : 'nota-mensual' }}">

                            {{ $resultado['nota'] ?? '—' }}

                        </td>

                    @endforeach

                    <td class="promedio-conducta">

                        {{ $fila['promedio'] !== null
                            ? number_format($fila['promedio'], 2)
                            : '—' }}

                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    <div class="separador-semestre"></div>

@empty

    <div class="aviso-provisional">
        No existen cadetes asignados al período
        y semestre seleccionados.
    </div>

@endforelse

<div class="nota-reporte">

    La calificación mensual considera las horas de sanción,
    los deméritos confirmados y los méritos vigentes aplicables.

    @if ($provisional)

        <br>

        El promedio es provisional e incluye el mes actual,
        cuando corresponde. Los meses futuros no se contabilizan.

    @endif

    @if (count($meses) !== 6)

        <br>

        El período seleccionado comprende {{ count($meses) }}
        meses calendario. El promedio requiere validación
        antes de utilizarse como calificación semestral oficial.

    @endif

    <br><br>

    Fecha de generación:
    {{ $fechaGeneracion->format('d/m/Y H:i') }}

</div>
