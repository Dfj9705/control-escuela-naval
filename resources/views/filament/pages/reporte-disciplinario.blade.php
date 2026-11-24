
<x-filament-panels::page>

<x-filament::section>
    <x-slot name="heading">
        Reportes disciplinarios
    </x-slot>

    <p class="text-sm text-gray-600 dark:text-gray-400">
        Consulte los acumulados de sanciones disciplinarias
        y las calificaciones mensuales y semestrales
        de conducta de los cadetes.
    </p>
</x-filament::section>



    <x-filament::section>
        <x-slot name="heading">
            Filtros del reporte
        </x-slot>

        {{ $this->form }}
    </x-filament::section>

<x-filament::tabs label="Reportes disciplinarios">

    <x-filament::tabs.item
        :active="$reporteActivo === 'general'"
        wire:click="cambiarReporte('general')"
        icon="heroicon-o-chart-bar-square"
    >
        Acumulado disciplinario
    </x-filament::tabs.item>

    <x-filament::tabs.item
        :active="$reporteActivo === 'conducta'"
        wire:click="cambiarReporte('conducta')"
        icon="heroicon-o-academic-cap"
    >
        Calificación de conducta
    </x-filament::tabs.item>

</x-filament::tabs>


@if ($reporteActivo === 'general')
<x-filament::section>
    <x-slot name="heading">
        Acumulado de sanciones disciplinarias
    </x-slot>
@php
    $resultados = $this->obtenerResultados();
    $resumen = $this->obtenerResumen($resultados);
@endphp


<div style="
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
">
   @foreach ([
    'Cadetes' => $resumen['cadetes'],
    'Faltas' => $resumen['faltas'],
    'Horas' => $resumen['horas'],
    'Deméritos' => $resumen['demeritos'],
    'Méritos' => $resumen['meritos'],
    'Con deméritos' => $resumen['cadetes_con_demeritos'],
] as $titulo => $valor)

        <div class="rounded-xl border border-gray-200
                    p-4 dark:border-gray-700">

            <p class="text-sm text-gray-500">
                {{ $titulo }}
            </p>

            <p class="mt-2 text-2xl font-bold">
                {{ number_format($valor) }}
            </p>

        </div>

    @endforeach

</div>


    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="border-b dark:border-gray-700">
                <tr>
                    <th class="px-3 py-3">Catálogo</th>
                    <th class="px-3 py-3">Grado</th>
                    <th class="px-3 py-3">Cadete</th>
                    <th class="px-3 py-3">Semestre</th>
                    <th class="px-3 py-3 text-center">Faltas</th>
                    <th class="px-3 py-3 text-center">Horas</th>
                    <th class="px-3 py-3 text-center">Deméritos</th>
                    <th class="px-3 py-3 text-center">Méritos</th>
                    <th class="px-3 py-3 text-center">Límite</th>
                    <th class="px-3 py-3 text-center">% del límite</th>
                    <th class="px-3 py-3 text-center">Detalle</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($resultados as $fila)
                    <tr class="border-b dark:border-gray-700">
                        <td class="px-3 py-3">
                            {{ $fila['catalogo'] }}
                        </td>

                        <td class="px-3 py-3">
                            {{ $fila['grado'] }}
                        </td>

                        <td class="px-3 py-3">
                            {{ $fila['nombre'] }}
                        </td>

                        <td class="px-3 py-3">
                            {{ $fila['semestre'] }}
                        </td>

                        <td class="px-3 py-3 text-center">
                            {{ $fila['faltas'] }}
                        </td>

                        <td class="px-3 py-3 text-center">
                            {{ $fila['horas'] }}
                        </td>

                        <td class="px-3 py-3 text-center">
                            {{ $fila['demeritos'] }}
                        </td>
<td class="px-3 py-3 text-center">
    {{ $fila['meritos'] }}
</td>
                        <td class="px-3 py-3 text-center">
                            {{ $fila['limite'] ?? '—' }}
                        </td>

                        <td class="px-3 py-3 text-center">
    {{ $fila['porcentaje'] !== null
        ? number_format($fila['porcentaje'], 2) . '%'
        : '—' }}
</td>
<td class="px-3 py-3 text-center">
    <button
        type="button"
        wire:click="seleccionarCadete({{ $fila['cadete_id'] }})"
        class="text-primary-600 hover:underline"
    >
        {{ $cadeteDetalleId === $fila['cadete_id']
            ? 'Ocultar'
            : 'Ver detalle' }}
    </button>
</td>
                    </tr>

@if ($this->cadeteDetalleId === $fila['cadete_id'])
    <tr>
        <td colspan="11" class="px-4 py-4">
            @php
                $sanciones = $this->obtenerDetalleSanciones(
                    $fila['cadete_id']
                );

                $meritos = $this->obtenerDetalleMeritos(
                    $fila['cadete_id']
                );
            @endphp

            <h4 class="font-bold" style="margin-bottom: 0.75rem;">
    Sanciones disciplinarias confirmadas
</h4>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="p-2 text-left">Fecha</th>
                            <th class="p-2 text-left">Falta</th>
                            <th class="p-2 text-left">Tipo</th>
                            <th class="p-2 text-center">Cantidad</th>
                            <th class="p-2 text-left">Hechos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sanciones as $sancion)
                            <tr class="border-b dark:border-gray-700">
                                <td class="p-2">
                                    {{ $sancion['fecha'] }}
                                </td>
                                <td class="p-2">
                                    {{ $sancion['falta'] }}
                                </td>
                                <td class="p-2">
                                    {{ ucfirst($sancion['tipo']) }}
                                </td>
                                <td class="p-2 text-center">
                                    {{ $sancion['cantidad'] ?? '—' }}
                                </td>
                                <td class="p-2">
                                    {{ $sancion['hechos'] ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-3 text-center">
                                    No hay sanciones confirmadas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


<div style="margin-top: 1.5rem;">

    <h4 class="font-bold" style="margin-bottom: 0.75rem;">
        Méritos disciplinarios vigentes
    </h4>

    <div class="overflow-x-auto">

        <table class="w-full text-sm">

            <thead class="border-b dark:border-gray-700">
                <tr>
                    <th class="p-2 text-left">Fecha</th>
                    <th class="p-2 text-center">Cantidad</th>
                    <th class="p-2 text-left">Motivo</th>
                    <th class="p-2 text-left">Responsable</th>
                </tr>
            </thead>

            <tbody>

                @forelse ($meritos as $merito)

                    <tr class="border-b dark:border-gray-700">

                        <td class="p-2">
                            {{ $merito['fecha'] }}
                        </td>

                        <td class="p-2 text-center font-semibold
                                   text-success-600">
                            {{ $merito['cantidad'] }}
                        </td>

                        <td class="p-2">
                            {{ $merito['motivo'] }}
                        </td>

                        <td class="p-2">
                            {{ $merito['responsable'] }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4"
                            class="p-3 text-center text-gray-500">

                            No hay méritos registrados
                            durante el período seleccionado.

                        </td>
                    </tr>

                @endforelse

            </tbody>

            @if (count($meritos) > 0)

                <tfoot class="border-t dark:border-gray-700">
                    <tr>
                        <td class="p-2 font-bold">
                            Total
                        </td>

                        <td class="p-2 text-center font-bold">
                            {{ array_sum(array_column($meritos, 'cantidad')) }}
                        </td>

                        <td colspan="2"></td>
                    </tr>
                </tfoot>

            @endif

        </table>

    </div>

</div>

        </td>
    </tr>
@endif

                @empty
                    <tr>
                        <td colspan="11"
                            class="px-3 py-6 text-center text-gray-500">
                            No existen cadetes asignados
                            al período y semestre seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament::section>




@elseif ($reporteActivo === 'conducta')

    <x-filament::section>
        <x-slot name="heading">
            Calificación mensual y semestral de conducta
        </x-slot>

        @php
            $meses = $this->obtenerMesesPeriodo();

            $calificaciones = collect(
                $this->obtenerCalificacionesConducta()
            )->groupBy('semestre');
        @endphp

        @forelse ($calificaciones as $semestre => $cadetes)

            <div style="margin-bottom: 2rem;">

                <h3 class="text-lg font-bold"
                    style="margin-bottom: 1rem;">
                    {{ mb_strtoupper($semestre) }}
                </h3>

                <div class="overflow-x-auto">

                    <table class="w-full text-sm text-left">

                        <thead class="border-b dark:border-gray-700">
                            <tr>
                                <th class="p-3">No.</th>
                                <th class="p-3">Grado</th>
                                <th class="p-3">Cadete</th>

                                @foreach ($meses as $mes)
                                    <th class="p-3 text-center">
                                        {{ $mes['nombre'] }}
                                    </th>
                                @endforeach

                                <th class="p-3 text-center">
                                    Promedio
                                </th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($cadetes as $fila)

                                <tr class="border-b dark:border-gray-700">

                                    <td class="p-3">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="p-3">
                                        {{ $fila['grado'] }}
                                    </td>

                                    <td class="p-3">
                                        {{ $fila['nombre'] }}
                                    </td>

                                    @foreach ($meses as $mes)

                                        @php
                                            $resultado = $fila['calificaciones'][
                                                $mes['clave']
                                            ] ?? null;
                                        @endphp


<td class="p-3 text-center">

    @if ($resultado !== null)

        <x-filament::modal width="md">

            <x-slot name="trigger">
                <button
                    type="button"
                    class="font-semibold text-primary-600 hover:underline"
                    title="Consultar detalle de la calificación"
                >
                    {{ $resultado['nota'] }}
                </button>
            </x-slot>

            <x-slot name="heading">
                Detalle de calificación
            </x-slot>

            <x-slot name="description">
                {{ $fila['nombre'] }} —
                {{ $mes['nombre'] }} {{ $mes['anio'] }}
            </x-slot>

            <div class="space-y-3">

                @foreach ([
                    'Calificación inicial' => 100,
                    'Horas de sanción' => $resultado['horas'],
                    'Deméritos registrados' => $resultado['demeritos'],
                    'Méritos otorgados' => $resultado['meritos'],
                    'Méritos aplicados' => $resultado['meritos_aplicados'],
                    'Deméritos netos' => $resultado['demeritos_netos'],
                    'Descuento total' => 100 - $resultado['nota'],
                ] as $concepto => $valor)

                    <div style="
                        display: flex;
                        justify-content: space-between;
                        gap: 1rem;
                        padding: 0.5rem 0;
                    "
                    class="border-b dark:border-gray-700">

                        <span>{{ $concepto }}</span>

                        <span class="font-semibold">
                            {{ $valor }}
                        </span>

                    </div>

                @endforeach

                <div class="rounded-xl border p-4 text-center
                            dark:border-gray-700">

                    <p class="text-sm text-gray-500">
                        Calificación mensual de conducta
                    </p>

                    <p class="text-3xl font-bold">
                        {{ $resultado['nota'] }}
                    </p>

                    <p class="text-sm text-gray-500">
                        100 - {{ $resultado['horas'] }}
                        - (2 × {{ $resultado['demeritos_netos'] }})
                    </p>

                </div>

            </div>

        </x-filament::modal>

    @else

        —

    @endif

</td>


                                    @endforeach

                                    <td class="p-3 text-center font-bold">

                                        {{ $fila['promedio'] !== null
                                            ? number_format($fila['promedio'], 2)
                                            : '—' }}

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        @empty

            <p class="text-sm text-gray-500">
                No existen cadetes asignados al período
                y semestre seleccionados.
            </p>

        @endforelse

        <p class="text-sm text-gray-500">
            Durante el período en curso, el promedio es
            provisional e incluye el mes actual.
            Los meses futuros no se contabilizan.
        </p>

    </x-filament::section>


@endif

</x-filament-panels::page>
