<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromocionResource\Pages;
use App\Filament\Resources\PromocionResource\RelationManagers;
use App\Models\Promocion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromocionResource extends Resource
{
    protected static ?string $model = Promocion::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Gestión de cadetes';

    protected static ?string $navigationLabel = 'Promociones';

    protected static ?string $modelLabel = 'Promoción';

    protected static ?string $pluralModelLabel = 'Promociones';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la promoción')
                    ->schema([
                        Forms\Components\TextInput::make('numero')
                            ->label('Número de promoción')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('anio_ingreso')
                            ->label('Año de ingreso')
                            ->numeric()
                            ->integer()
                            ->minValue(1900)
                            ->maxValue(2100),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('Promoción')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('anio_ingreso')
                    ->label('Año de ingreso')
                    ->sortable(),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(60)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cadetes_count')
                    ->label('Cadetes')
                    ->counts('cadetes'),
            ])
            ->defaultSort('numero', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPromocions::route('/'),
            'create' => Pages\CreatePromocion::route('/create'),
            'edit' => Pages\EditPromocion::route('/{record}/edit'),
        ];
    }
}
