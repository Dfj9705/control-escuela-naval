<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeritoDisciplinarioResource\Pages;
use App\Filament\Resources\MeritoDisciplinarioResource\RelationManagers;
use App\Models\Cadete;
use App\Models\MeritoDisciplinario;
use App\Models\Persona;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MeritoDisciplinarioResource extends Resource
{
    protected static ?string $model = MeritoDisciplinario::class;


    protected static ?string $navigationGroup = 'Gestión disciplinaria';

    protected static ?string $navigationLabel = 'Méritos disciplinarios';

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Mérito disciplinario';

    protected static ?string $pluralModelLabel = 'Méritos disciplinarios';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cadete_id')
                    ->label('Cadete')
                    ->relationship(
                        name: 'cadete',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn($query) =>
                            $query->with('persona')
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn(Cadete $record) =>
                            $record->persona?->nombre_completo
                            ?? "Cadete #{$record->id}"
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\DatePicker::make('fecha_merito')
                    ->label('Fecha del mérito')
                    ->default(now())
                    ->required(),

                Forms\Components\TextInput::make('cantidad')
                    ->label('Cantidad de méritos')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    ->required(),

                Forms\Components\Select::make('responsable_id')
                    ->label('Responsable que autoriza')
                    ->relationship(
                        name: 'responsable',
                        titleAttribute: 'primer_nombre'
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn(Persona $record) =>
                            $record->nombre_completo
                    )
                    ->searchable([
                        'primer_nombre',
                        'primer_apellido',
                        'catalogo',
                    ])
                    ->preload()
                    ->required(),

                Forms\Components\Textarea::make('motivo')
                    ->label('Motivo del mérito')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cadete.persona.catalogo')
                    ->label('Catálogo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cadete.persona.primer_nombre')
                    ->label('Cadete')
                    ->formatStateUsing(
                        fn(MeritoDisciplinario $record) =>
                            $record->cadete?->persona?->nombre_completo
                    )
                    ->searchable(),

                Tables\Columns\TextColumn::make('fecha_merito')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Méritos')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('responsable.primer_nombre')
                    ->label('Responsable')
                    ->formatStateUsing(
                        fn(MeritoDisciplinario $record) =>
                            $record->responsable?->nombre_completo
                    ),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'success' => MeritoDisciplinario::ESTADO_VIGENTE,
                        'danger' => MeritoDisciplinario::ESTADO_ANULADO,
                    ]),
            ])
            ->defaultSort('fecha_merito', 'desc')

            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(
                        fn(MeritoDisciplinario $record): bool =>
                            $record->estado === MeritoDisciplinario::ESTADO_VIGENTE
                            && auth()->user()?->hasRole('superadministrador')
                    )
                    ->form([
                        Forms\Components\Textarea::make('motivo_anulacion')
                            ->label('Motivo de anulación')
                            ->required()
                            ->minLength(10)
                            ->maxLength(2000)
                            ->rows(3),
                    ])
                    ->modalHeading('Anular mérito disciplinario')
                    ->modalDescription(
                        'El mérito dejará de contabilizarse en la calificación de conducta. '
                        . 'Su registro permanecerá en el historial.'
                    )
                    ->modalSubmitActionLabel('Confirmar anulación')
                    ->action(function (MeritoDisciplinario $record, array $data): void {
                        $actualizados = MeritoDisciplinario::query()
                            ->whereKey($record->getKey())
                            ->where(
                                'estado',
                                MeritoDisciplinario::ESTADO_VIGENTE
                            )
                            ->update([
                                'estado' => MeritoDisciplinario::ESTADO_ANULADO,
                                'motivo_anulacion' => $data['motivo_anulacion'],
                                'anulado_por' => auth()->id(),
                                'anulado_at' => now(),
                            ]);

                        if ($actualizados === 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('El mérito ya no está vigente')
                                ->warning()
                                ->send();

                            return;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Mérito anulado correctamente')
                            ->success()
                            ->send();
                    }),
            ])

            ->bulkActions([]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeritoDisciplinarios::route('/'),
            'create' => Pages\CreateMeritoDisciplinario::route('/create'),
            'edit' => Pages\EditMeritoDisciplinario::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('superadministrador') ?? false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
