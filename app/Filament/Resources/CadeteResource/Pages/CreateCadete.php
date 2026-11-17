<?php

namespace App\Filament\Resources\CadeteResource\Pages;

use App\Filament\Resources\CadeteResource;
use App\Models\Cadete;
use App\Models\Persona;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateCadete extends CreateRecord
{
    protected static string $resource = CadeteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            // 1. Separar los datos correspondientes a Persona
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

            // 2. Crear la persona
            $persona = Persona::create($datosPersona);

            // 3. Separar los datos correspondientes a Cadete
            $datosCadete = Arr::only($data, [
                'grado_id',
                'promocion_id',
                'fecha_ingreso',
                'estado',
            ]);

            // 4. Asociar la persona creada
            $datosCadete['persona_id'] = $persona->id;

            // 5. Crear el cadete
            return Cadete::create($datosCadete);
        });
    }
}
