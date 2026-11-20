<?php

namespace App\Filament\Resources\CadeteResource\RelationManagers;

use App\Filament\Resources\CadeteResource;
use App\Models\Peloton;
use App\Services\Cadetes\AsignarPeloton;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class AsignacionesPelotonRelationManager extends RelationManager
{
    protected static string $relationship = 'asignacionesPeloton';

    protected static ?string $title = 'Historial de pelotones';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return CadeteResource::canEdit($ownerRecord);
    }

    public function mount(): void
    {
        abort_unless(static::canViewForRecord($this->getOwnerRecord(), $this->getPageClass()), 403);

        parent::mount();
    }

    public function hydrate(): void
    {
        abort_unless(static::canViewForRecord($this->getOwnerRecord(), $this->getPageClass()), 403);
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('peloton.nombre')
                    ->label('Pelotón')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_fin')
                    ->label('Fecha de finalización')
                    ->date('d/m/Y')
                    ->placeholder('Vigente'),

                Tables\Columns\IconColumn::make('vigente')
                    ->label('Actual')
                    ->boolean()
                    ->state(
                        fn($record): bool =>
                            $record->fecha_fin === null
                    ),
            ])
            ->headerActions([
                Tables\Actions\Action::make('asignarPeloton')
                    ->label('Asignar pelotón')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->authorize(fn (): bool => auth()->user()?->can('cadete_pelotones.asignar') ?? false)
                    ->form([
                        Forms\Components\Select::make('peloton_id')
                            ->label('Pelotón')
                            ->options(
                                fn() => Peloton::query()
                                    ->where('activo', true)
                                    ->orderBy('nombre')
                                    ->pluck('nombre', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->required(),

                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha de inicio')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        try {

                            app(AsignarPeloton::class)->handle(
                                $this->getOwnerRecord()->id,
                                (int) $data['peloton_id'],
                                $data['fecha_inicio']
                            );

                            Notification::make()
                                ->title('Pelotón asignado correctamente')
                                ->success()
                                ->send();

                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo asignar el pelotón')
                                ->body(
                                    collect($e->errors())
                                        ->flatten()
                                        ->implode("\n")
                                )
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('fecha_inicio', 'desc');
    }
}
