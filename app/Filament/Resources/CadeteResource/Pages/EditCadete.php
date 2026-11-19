<?php

namespace App\Filament\Resources\CadeteResource\Pages;

use App\Filament\Resources\CadeteResource;
use App\Filament\Widgets\ResumenDisciplinarioCadete;
use App\Models\Cadete;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditCadete extends EditRecord
{
    protected static string $resource = CadeteResource::class;

    /**
     * Cargar los datos de Persona en el formulario.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $persona = $this->record->persona;

        if ($persona) {
            $data = array_merge(
                $data,
                $persona->only([
                    'catalogo',
                    'primer_nombre',
                    'segundo_nombre',
                    'primer_apellido',
                    'segundo_apellido',
                    'foto',
                    'etnia',
                    'grupo_sanguineo',
                    'tipo_documento',
                    'numero_documento',
                    'nit',
                    'telefono',
                    'direccion',
                    'correo_electronico',
                    'fecha_nacimiento',
                ])
            );
        }

        return $data;
    }

    /**
     * Actualizar Persona y Cadete en una transacción.
     */
    protected function handleRecordUpdate(
        Model $record,
        array $data
    ): Model {
        return DB::transaction(function () use ($record, $data) {

            // 1. Obtener los datos personales
            $datosPersona = Arr::only($data, [
                'catalogo',
                'primer_nombre',
                'segundo_nombre',
                'primer_apellido',
                'segundo_apellido',
                'foto',
                'etnia',
                'grupo_sanguineo',
                'tipo_documento',
                'numero_documento',
                'nit',
                'telefono',
                'direccion',
                'correo_electronico',
                'fecha_nacimiento',
            ]);

            // 2. Actualizar la persona asociada
            $record->persona->update($datosPersona);

            // 3. Obtener los datos del cadete
            $datosCadete = Arr::only($data, [
                'grado_id',
                'promocion_id',
                'fecha_ingreso',
                'estado',
            ]);

            // 4. Actualizar el cadete
            $record->update($datosCadete);

            return $record;
        });
    }

    /**
     * Por ahora no permitiremos eliminar cadetes.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        if (!auth()->user()?->can('sanciones_disciplinarias.ver')) {
            return [];
        }

        return [
            ResumenDisciplinarioCadete::class,
        ];
    }
}
