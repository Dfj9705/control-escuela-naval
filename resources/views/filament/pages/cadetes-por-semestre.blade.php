<x-filament-panels::page>
    @php
        $resumen = $this->getResumenSemestres();
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            Resumen de cadetes por semestre
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($resumen['semestres'] as $semestre)
                <div class="rounded-lg border p-4 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $semestre['nombre'] }}
                    </div>

                    <div class="mt-2 text-2xl font-bold">
                        {{ $semestre['total'] }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    No hay asignaciones para el período seleccionado.
                </p>
            @endforelse
        </div>

        <div class="mt-4 border-t pt-4 dark:border-gray-700">
            <span class="font-semibold">
                Total de cadetes:
            </span>

            {{ $resumen['total'] }}
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
