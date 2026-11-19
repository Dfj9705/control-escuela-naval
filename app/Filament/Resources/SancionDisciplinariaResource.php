<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SancionDisciplinariaResource\Pages;
use App\Models\Cadete;
use App\Models\FaltaDisciplinaria;
use App\Models\Persona;
use App\Models\SancionDisciplinaria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
class SancionDisciplinariaResource extends Resource
{
    protected static ?string $model = SancionDisciplinaria::class;

    protected static ?string $navigationIcon =
        'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup =
        'Gestión disciplinaria';

    protected static ?string $navigationLabel =
        'Sanciones disciplinarias';

    protected static ?string $modelLabel =
        'Sanción disciplinaria';

    protected static ?string $pluralModelLabel =
        'Sanciones disciplinarias';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'sanciones_disciplinarias.ver'
        ) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(
            'sanciones_disciplinarias.crear'
        ) ?? false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }


    public static function canView($record): bool
    {
        return auth()->user()?->can(
            'sanciones_disciplinarias.ver'
        ) ?? false;
    }


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información de la falta')
                ->schema([
                    Forms\Components\Select::make('cadete_id')
                        ->label('Cadete')
                        ->options(
                            fn() => Cadete::query()
                                ->where('estado', 'activo')
                                ->with('persona')
                                ->get()
                                ->mapWithKeys(fn(Cadete $cadete) => [
                                    $cadete->id =>
                                        $cadete->persona?->nombre_completo
                                        ?? "Cadete #{$cadete->id}",
                                ])
                                ->all()
                        )
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('falta_disciplinaria_id')
                        ->label('Falta disciplinaria')
                        ->options(
                            fn() => FaltaDisciplinaria::query()
                                ->where('activo', true)
                                ->where('version_reglamento', '2026')
                                ->whereIn('tipo', ['leve', 'grave'])
                                ->orderBy('tipo')
                                ->orderBy('numeral')
                                ->get()
                                ->mapWithKeys(fn($falta) => [
                                    $falta->id =>
                                        ucfirst($falta->tipo)
                                        . " #{$falta->numeral} - "
                                        . $falta->descripcion,
                                ])
                                ->all()
                        )
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('responsable_id')
                        ->label('Responsable de imponer la sanción')
                        ->options(
                            fn() => Persona::query()
                                ->orderBy('primer_nombre')
                                ->get()
                                ->mapWithKeys(fn(Persona $persona) => [
                                    $persona->id =>
                                        $persona->nombre_completo,
                                ])
                                ->all()
                        )
                        ->searchable()
                        ->required(),

                    Forms\Components\DatePicker::make('fecha_falta')
                        ->label('Fecha de la falta')
                        ->default(today())
                        ->maxDate(today())
                        ->required(),

                    Forms\Components\Textarea::make('descripcion_hechos')
                        ->label('Descripción de los hechos')
                        ->rows(4)
                        ->maxLength(10000)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No.')
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'cadete.persona.nombre_completo'
                )
                    ->label('Cadete')
                    ->searchable(),

                Tables\Columns\TextColumn::make(
                    'faltaDisciplinaria.descripcion'
                )
                    ->label('Falta')
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\TextColumn::make('fecha_falta')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cantidad_aplicada')
                    ->label('Cantidad'),

                Tables\Columns\TextColumn::make('tipo_sancion')
                    ->label('Unidad'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
            ])
            ->defaultSort('id', 'desc')

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver detalle')
                    ->icon('heroicon-o-eye'),
            ])

            ->bulkActions([]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSancionDisciplinarias::route('/'),
            'create' => Pages\CreateSancionDisciplinaria::route('/create'),
            'view' => Pages\ViewSancionDisciplinaria::route('/{record}'),
        ];
    }


    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            \Filament\Infolists\Components\Section::make(
                'Información disciplinaria'
            )
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make(
                        'cadete.persona.nombre_completo'
                    )->label('Cadete'),

                    \Filament\Infolists\Components\TextEntry::make(
                        'faltaDisciplinaria.descripcion'
                    )->label('Falta disciplinaria')
                        ->columnSpanFull(),

                    \Filament\Infolists\Components\TextEntry::make(
                        'responsable.nombre_completo'
                    )->label('Responsable'),

                    \Filament\Infolists\Components\TextEntry::make(
                        'fecha_falta'
                    )->label('Fecha de la falta')
                        ->date('d/m/Y'),

                    \Filament\Infolists\Components\TextEntry::make(
                        'descripcion_hechos'
                    )->label('Descripción de los hechos')
                        ->placeholder('Sin observaciones')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            \Filament\Infolists\Components\Section::make(
                'Sanción y seguimiento'
            )
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make(
                        'cantidad_aplicada'
                    )->label('Cantidad'),

                    \Filament\Infolists\Components\TextEntry::make(
                        'tipo_sancion'
                    )->label('Unidad'),

                    \Filament\Infolists\Components\TextEntry::make(
                        'estado'
                    )->label('Estado')
                        ->badge(),

                    \Filament\Infolists\Components\TextEntry::make(
                        'registradoPor.name'
                    )->label('Registrado por'),

                    \Filament\Infolists\Components\TextEntry::make(
                        'created_at'
                    )->label('Fecha de registro')
                        ->dateTime('d/m/Y H:i'),
                ])
                ->columns(2),

            \Filament\Infolists\Components\TextEntry::make(
                'confirmadoPor.name'
            )
                ->label('Confirmado por')
                ->placeholder('Sin confirmar'),

            \Filament\Infolists\Components\TextEntry::make(
                'confirmado_at'
            )
                ->label('Fecha de confirmación')
                ->dateTime('d/m/Y H:i')
                ->placeholder('Sin confirmar'),


            \Filament\Infolists\Components\TextEntry::make(
                'motivo_anulacion'
            )
                ->label('Motivo de anulación')
                ->columnSpanFull()
                ->visible(
                    fn($record): bool =>
                        $record->estado === SancionDisciplinaria::ESTADO_ANULADA
                ),

            \Filament\Infolists\Components\TextEntry::make(
                'anuladoPor.name'
            )
                ->label('Anulado por')
                ->visible(
                    fn($record): bool =>
                        $record->estado === SancionDisciplinaria::ESTADO_ANULADA
                ),

            \Filament\Infolists\Components\TextEntry::make(
                'anulado_at'
            )
                ->label('Fecha de anulación')
                ->dateTime('d/m/Y H:i')
                ->visible(
                    fn($record): bool =>
                        $record->estado === SancionDisciplinaria::ESTADO_ANULADA
                ),
        ]);
    }


}
