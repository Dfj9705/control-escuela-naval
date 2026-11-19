


<div class="header">
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
        </tr>
    </table>

    <div class="document-title">
        BOLETA DE HORAS DE ARRESTO
    </div>

    <div class="document-number">
        Registro disciplinario No.
        {{ str_pad($sancion->id, 6, '0', STR_PAD_LEFT) }}
    </div>
</div>


    <div class="section-title">
        01 / DATOS DEL SANCIONADO
    </div>

    <table class="data-table">
        <tr>
            <td class="field-label">Grado:</td>
            <td>
                {{ $sancion->cadete->grado?->nombre ?? 'No registrado' }}
            </td>
        </tr>
        <tr>
            <td class="field-label">Nombre:</td>
            <td>
                {{ $sancion->cadete->persona->nombre_completo }}
            </td>
        </tr>


        <tr>
            <td class="field-label">Semestre:</td>
           <td>
                {{ $semestre }}
            </td>
        </tr>

        <tr>
            <td class="field-label">Pelotón:</td>
           <td>
                {{ $peloton }}
            </td>
        </tr>
    </table>

    <div class="section-title">
        02 / INFORMACIÓN DE LA FALTA
    </div>

    <table class="data-table">
        <tr>
            <td class="field-label">Fecha de la falta:</td>
            <td>
                {{ $sancion->fecha_falta->format('d/m/Y') }}
            </td>
        </tr>

        <tr>
            <td class="field-label">Motivo:</td>
            <td>
                {{ $sancion->faltaDisciplinaria->descripcion }}
            </td>
        </tr>

        <tr>
            <td class="field-label">Justificación:</td>
            <td>
                {{ $sancion->descripcion_hechos ?: 'Sin descripción adicional' }}
            </td>
        </tr>
    </table>

    <div class="section-title">
        03 / SANCIÓN APLICADA
    </div>

    <div class="highlight">
        <div class="highlight-value">
            {{ $sancion->cantidad_aplicada }}
            HORAS DE ARRESTO
        </div>
    </div>

    <div class="section-title">
        04 / RESPONSABLE DE LA SANCIÓN
    </div>

    <table class="data-table">
        <tr>
            <td class="field-label">Nombre:</td>
            <td>
                {{ $sancion->responsable->nombre_completo }}
            </td>
        </tr>
    </table>




<table class="tabla-firmas">
    <tr>
        <td>
            <div class="espacio-firma"></div>
            <div class="linea-firma">
                Firma de quien sancionó
            </div>
        </td>
        <td>
            <div class="espacio-firma"></div>
            <div class="linea-firma">
                Firma del sancionado
            </div>
        </td>
    </tr>
</table>


<div class="conforme">
    <strong>ES CONFORME</strong>

    <div class="espacio-firma"></div>

    <div class="linea-firma">
        Comandante de Cuerpo
    </div>
</div>

