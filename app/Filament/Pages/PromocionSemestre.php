<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\PeriodoAcademico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\AsignacionSemestre;

use Filament\Actions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;

use App\Services\PromocionSemestreService;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Throwable;
class PromocionSemestre extends Page
{
    use InteractsWithForms;
    use InteractsWithActions;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationGroup = 'Organización académica';

    protected static ?string $navigationLabel = 'Promoción de semestre';

    protected static ?string $title = 'Promoción de semestre';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.promocion-semestre';

    public array $cadetesSeleccionados = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'periodo_origen_id' => null,
            'periodo_destino_id' => PeriodoAcademico::query()
                ->where('activo', true)
                ->value('id'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Períodos académicos')
                    ->description(
                        'Seleccione el período de origen y el período al que serán promovidos los cadetes.'
                    )
                    ->schema([
                        Forms\Components\Select::make('periodo_origen_id')
                            ->label('Período de origen')
                            ->options(fn(): array => $this->opcionesPeriodos())
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('periodo_destino_id')
                            ->label('Período de destino')
                            ->options(fn(): array => $this->opcionesPeriodos())
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->different('periodo_origen_id')
                            ->validationMessages([
                                'different' => 'El período de destino debe ser diferente al de origen.',
                            ]),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function opcionesPeriodos(): array
    {
        return PeriodoAcademico::query()
            ->orderByDesc('anio')
            ->orderByDesc('numero')
            ->get()
            ->mapWithKeys(fn(PeriodoAcademico $periodo): array => [
                $periodo->id => "{$periodo->anio}-"
                    . ($periodo->numero === 1 ? 'I' : 'II'),
            ])
            ->all();
    }

    public function getCadetesDisponibles()
    {
        $periodoOrigenId = data_get(
            $this->data,
            'periodo_origen_id'
        );

        if (blank($periodoOrigenId)) {
            return collect();
        }

        return AsignacionSemestre::query()
            ->with([
                'cadete.persona',
                'cadete.grado',
                'cadete.promocion',
                'semestre',
            ])
            ->where('periodo_academico_id', $periodoOrigenId)
            ->whereHas('cadete', function ($query) {
                $query->where('estado', 'activo');
            })
            ->orderBy('semestre_id')
            ->get();
    }

    public function updatedDataPeriodoOrigenId(): void
    {
        $this->cadetesSeleccionados = [];
    }

    public function seleccionarTodos(): void
    {
        $this->cadetesSeleccionados = $this->getCadetesDisponibles()
            ->filter(
                fn($asignacion) =>
                    $asignacion->semestre !== null
                    && $asignacion->semestre->numero < 8
            )
            ->pluck('cadete_id')
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();
    }

    public function deseleccionarTodos(): void
    {
        $this->cadetesSeleccionados = [];
    }

    public function confirmarPromocionAction(): Actions\Action
    {
        return Actions\Action::make('confirmarPromocion')
            ->label('Promover cadetes')
            ->icon('heroicon-o-arrow-trending-up')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirmar promoción de semestre')
            ->modalDescription(function (): string {
                $cantidad = count($this->cadetesSeleccionados);

                return "Se promoverán {$cantidad} cadetes al siguiente semestre. "
                    . 'Esta operación creará nuevas asignaciones académicas '
                    . 'y conservará el historial anterior.';
            })
            ->modalSubmitActionLabel('Confirmar promoción')
            ->action(function (): void {
                try {
                    // Validar los campos del formulario.
                    $data = $this->form->getState();

                    $cantidad = app(PromocionSemestreService::class)
                        ->promover(
                            (int) $data['periodo_origen_id'],
                            (int) $data['periodo_destino_id'],
                            $this->cadetesSeleccionados
                        );

                    // Limpiar la selección después del éxito.
                    $this->cadetesSeleccionados = [];

                    Notification::make()
                        ->title('Promoción completada')
                        ->body("Se promovieron {$cantidad} cadetes correctamente.")
                        ->success()
                        ->send();

                } catch (ValidationException $e) {
                    Notification::make()
                        ->title('No se pudo realizar la promoción')
                        ->body(collect($e->errors())->flatten()->first())
                        ->danger()
                        ->send();

                } catch (QueryException $e) {
                    report($e);

                    Notification::make()
                        ->title('No se pudo realizar la promoción')
                        ->body('Ocurrió un problema al guardar las asignaciones. Verifique que no existan duplicados.')
                        ->danger()
                        ->send();

                } catch (Throwable $e) {
                    report($e);

                    Notification::make()
                        ->title('Error al realizar la promoción')
                        ->body('Ocurrió un error inesperado. No se completó la operación.')
                        ->danger()
                        ->send();
                }
            });
    }
}
