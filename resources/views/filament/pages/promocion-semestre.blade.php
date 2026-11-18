<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">
            Selección de períodos académicos
        </x-slot>

        {{ $this->form }}
    </x-filament::section>

    @php
        $asignaciones = $this->getCadetesDisponibles();
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            Cadetes disponibles para promoción
        </x-slot>

        <x-slot name="description">
            Seleccione un período de origen para consultar
            los cadetes que pueden ser promovidos.
        </x-slot>

        <div class="flex flex-wrap items-center gap-3 mb-4">
            <x-filament::button
                color="gray"
                size="sm"
                wire:click="seleccionarTodos"
            >
                Seleccionar todos
            </x-filament::button>

            <x-filament::button
                color="gray"
                size="sm"
                wire:click="deseleccionarTodos"
            >
                Deseleccionar todos
            </x-filament::button>

            <span class="text-sm text-gray-600 dark:text-gray-400">
                Seleccionados: {{ count($cadetesSeleccionados) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="border-b dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Seleccionar</th>
                        <th class="px-4 py-3">Catálogo</th>
                        <th class="px-4 py-3">Nombre completo</th>
                        <th class="px-4 py-3">Grado</th>
                        <th class="px-4 py-3">Promoción</th>
                        <th class="px-4 py-3">Semestre actual</th>
                        <th class="px-4 py-3">Siguiente semestre</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($asignaciones as $asignacion)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-3">
                                @if ($asignacion->semestre && $asignacion->semestre->numero < 8)
                                    <input
                                        type="checkbox"
                                        wire:model.live="cadetesSeleccionados"
                                        value="{{ $asignacion->cadete_id }}"
                                        class="rounded border-gray-300 text-primary-600
                                            focus:ring-primary-500 dark:bg-gray-800"
                                    >
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                                                        <td class="px-4 py-3">
                                {{ $asignacion->cadete?->persona?->catalogo }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $asignacion->cadete?->persona?->nombre_completo }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $asignacion->cadete?->grado?->abreviatura }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $asignacion->cadete?->promocion?->numero }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $asignacion->semestre?->nombre }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($asignacion->semestre?->numero < 8)
                                    {{ ($asignacion->semestre?->numero ?? 0) + 1 }}
                                @else
                                    No aplica
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                No hay cadetes disponibles para el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-sm font-semibold">
            Total de cadetes: {{ $asignaciones->count() }}
        </div>
    </x-filament::section>
    @if (count($cadetesSeleccionados) > 0)
        <div class="flex justify-end">
            {{ $this->confirmarPromocionAction }}
        </div>
    @endif

<x-filament-actions::modals />

</x-filament-panels::page>
