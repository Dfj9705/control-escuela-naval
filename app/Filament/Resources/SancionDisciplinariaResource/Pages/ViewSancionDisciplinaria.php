<?php

namespace App\Filament\Resources\SancionDisciplinariaResource\Pages;

use App\Filament\Resources\SancionDisciplinariaResource;
use App\Models\SancionDisciplinaria;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Services\Disciplina\EnviarSancionAFirma;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use App\Services\Disciplina\AdjuntarBoletaFirmada;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use App\Services\Disciplina\ConfirmarSancionDisciplinaria;
use App\Services\Disciplina\AnularSancionDisciplinaria;
use Filament\Forms\Components\Textarea;

class ViewSancionDisciplinaria extends ViewRecord
{
    protected static string $resource =
        SancionDisciplinariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('imprimirBoleta')
                ->label('Imprimir boleta')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->visible(
                    fn(): bool =>
                        auth()->user()?->can('sanciones_disciplinarias.imprimir')
                        && $this->record->tipo_sancion ===
                        SancionDisciplinaria::TIPO_HORAS
                        && $this->record->estado !==
                        SancionDisciplinaria::ESTADO_ANULADA
                )
                ->url(
                    fn(): string =>
                        route('disciplina.boleta-arresto', [
                            'sancion' => $this->record->id,
                        ])
                )
                ->openUrlInNewTab(),


            Actions\Action::make('enviarAFirma')
                ->label('Enviar a firma')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->visible(
                    fn(): bool =>
                        (auth()->user()?->can('sanciones_disciplinarias.enviar_firma') ?? false)
                        && $this->record->puedeEnviarseAFirma()
                )
                ->requiresConfirmation()
                ->modalHeading('Enviar sanción a firma')
                ->modalDescription(
                    'La sanción pasará al estado Pendiente de firma. '
                    . '¿Desea continuar?'
                )
                ->modalSubmitActionLabel('Sí, enviar a firma')
                ->action(function (EnviarSancionAFirma $servicio): void {
                    try {
                        $servicio->handle($this->record->id);

                        $this->record->refresh();

                        Notification::make()
                            ->title('Sanción enviada a firma')
                            ->body('La sanción ahora está pendiente de firma.')
                            ->success()
                            ->send();

                        $this->refreshFormData(['estado']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('No se pudo enviar la sanción a firma')
                            ->body(
                                collect($e->errors())
                                    ->flatten()
                                    ->implode("\n")
                            )
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('adjuntarBoleta')
                ->label('Adjuntar boleta firmada')
                ->icon('heroicon-o-paper-clip')
                ->color('success')
                ->visible(
                    fn(): bool =>
                        (auth()->user()?->can('sanciones_disciplinarias.adjuntar') ?? false)
                        && $this->record->estado ===
                        SancionDisciplinaria::ESTADO_PENDIENTE_FIRMA
                )
                ->form([
                    FileUpload::make('archivo')
                        ->label('Boleta firmada (PDF)')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->storeFiles(false)
                        ->required()
                        ->helperText('Adjunte la boleta firmada en formato PDF. Máximo 10 MB.'),
                ])
                ->modalHeading('Adjuntar boleta firmada')
                ->modalDescription(
                    'Al guardar el documento, la sanción pasará al estado '
                    . 'Pendiente de confirmación.'
                )
                ->modalSubmitActionLabel('Guardar boleta')
                ->action(function (array $data, AdjuntarBoletaFirmada $servicio): void {
                    try {
                        $archivo = $data['archivo'] ?? null;

                        if (!$archivo instanceof UploadedFile) {
                            throw ValidationException::withMessages([
                                'archivo' => 'Debe seleccionar un archivo PDF válido.',
                            ]);
                        }

                        $servicio->handle(
                            $this->record->id,
                            $archivo
                        );

                        $this->record->refresh();

                        Notification::make()
                            ->title('Boleta firmada adjuntada')
                            ->body('La sanción está pendiente de confirmación.')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'estado',
                            'boleta_firmada',
                        ]);

                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('No se pudo adjuntar la boleta')
                            ->body(
                                collect($e->errors())
                                    ->flatten()
                                    ->implode("\n")
                            )
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('confirmarSancion')
                ->label('Confirmar sanción')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(
                    fn(): bool =>
                        (auth()->user()?->can('sanciones_disciplinarias.confirmar') ?? false)
                        && $this->record->puedeConfirmarse()
                )
                ->requiresConfirmation()
                ->modalHeading('Confirmar sanción disciplinaria')
                ->modalDescription(
                    'Está por confirmar definitivamente esta sanción. '
                    . 'Verifique que la boleta firmada corresponda al cadete, '
                    . 'la falta y la cantidad registrada. '
                    . 'Esta operación quedará registrada con su usuario y fecha.'
                )
                ->modalSubmitActionLabel('Sí, confirmar sanción')
                ->action(function (ConfirmarSancionDisciplinaria $servicio): void {
                    try {
                        $servicio->handle($this->record->id);

                        $this->record->refresh();

                        Notification::make()
                            ->title('Sanción confirmada correctamente')
                            ->body(
                                'La sanción quedó confirmada y se registró '
                                . 'el usuario responsable.'
                            )
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'estado',
                            'confirmado_por',
                            'confirmado_at',
                        ]);

                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('No se pudo confirmar la sanción')
                            ->body(
                                collect($e->errors())
                                    ->flatten()
                                    ->implode("\n")
                            )
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('verBoletaFirmada')
                ->label('Ver boleta firmada')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('gray')
                ->visible(
                    fn(): bool =>
                        (auth()->user()?->can('sanciones_disciplinarias.ver') ?? false)
                        && !empty($this->record->boleta_firmada)
                        && $this->record->estado !==
                        SancionDisciplinaria::ESTADO_ANULADA
                )
                ->url(
                    fn(): string =>
                        route('disciplina.boleta-firmada', [
                            'sancion' => $this->record->id,
                        ])
                )
                ->openUrlInNewTab(),


            Actions\Action::make('anularSancion')
                ->label('Anular sanción')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(
                    fn(): bool =>
                        (auth()->user()?->can('sanciones_disciplinarias.anular') ?? false)
                        && $this->record->puedeAnularse()
                )
                ->form([
                    Textarea::make('motivo_anulacion')
                        ->label('Motivo de anulación')
                        ->required()
                        ->minLength(10)
                        ->maxLength(5000)
                        ->rows(4)
                        ->helperText(
                            'Explique por qué debe anularse esta sanción. '
                            . 'La justificación quedará registrada en el expediente.'
                        ),
                ])
                ->requiresConfirmation()
                ->modalHeading('Anular sanción disciplinaria')
                ->modalDescription(
                    'Esta operación anulará la sanción, incluso si ya fue '
                    . 'confirmada. Se conservarán sus datos y documentos '
                    . 'para consulta histórica.'
                )
                ->modalSubmitActionLabel('Sí, anular sanción')
                ->action(function (array $data, AnularSancionDisciplinaria $servicio): void {
                    try {
                        $servicio->handle(
                            $this->record->id,
                            $data['motivo_anulacion']
                        );

                        $this->record->refresh();

                        Notification::make()
                            ->title('Sanción anulada correctamente')
                            ->body(
                                'La anulación y su justificación '
                                . 'quedaron registradas.'
                            )
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'estado',
                            'motivo_anulacion',
                            'anulado_por',
                            'anulado_at',
                        ]);

                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('No se pudo anular la sanción')
                            ->body(
                                collect($e->errors())
                                    ->flatten()
                                    ->implode("\n")
                            )
                            ->danger()
                            ->send();
                    }
                }),



            Actions\Action::make('imprimirBoletaDemeritos')
                ->label('Imprimir boleta de deméritos')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->visible(
                    fn(): bool =>
                        $this->record->tipo_sancion ===
                        SancionDisciplinaria::TIPO_DEMERITOS
                        && $this->record->estado !==
                        SancionDisciplinaria::ESTADO_ANULADA
                        && auth()->user()?->can(
                            'sanciones_disciplinarias.imprimir'
                        )
                )
                ->url(fn(): string => route(
                    'disciplina.boleta-demeritos',
                    ['sancion' => $this->record]
                ))
                ->openUrlInNewTab(),



        ];
    }
}
