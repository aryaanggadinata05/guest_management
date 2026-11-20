<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableResource\Pages;
use App\Filament\Resources\TableResource\RelationManagers;
use App\Models\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables as FilamentTables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table as FilamentTable;
use Illuminate\Database\Eloquent\Builder;

class TableResource extends Resource
{
    protected static ?string $model = Table::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Manajemen Meja';

    protected static ?string $navigationGroup = 'Operasional Acara';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Meja';

    protected static ?string $pluralModelLabel = 'Manajemen Meja';

    protected static ?string $slug = 'manajemen-meja';

    // Batasi menu ini HANYA untuk Admin
    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_id')
                    ->relationship('event', 'name')
                    ->required()
                    ->label('Pilih Event'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama / Nomor Meja')
                    ->placeholder('Contoh: Meja 01'),
                Forms\Components\TextInput::make('capacity')
                    ->numeric()
                    ->required()
                    ->default(10)
                    ->label('Kapasitas Maksimal Kursi'),
                Forms\Components\TextInput::make('zone')
                    ->label('Zona / Area')
                    ->placeholder('Contoh: VVIP, VIP, Regular, Shareholder')
                    ->maxLength(255),
                Forms\Components\TextInput::make('pic_name')
                    ->label('Nama PIC / Koordinator Area')
                    ->placeholder('Contoh: Budi Santoso')
                    ->maxLength(255),
            ]);
    }

    public static function table(FilamentTable $table): FilamentTable
    {
        return $table
            // OPTIMASI: Eager Load relasi guests dan attendance sekaligus untuk mencegah N+1 Query
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['guests.attendance']))
            ->columns([
                FilamentTables\Columns\TextColumn::make('event.name')->label('Acara')->sortable()->searchable(),
                FilamentTables\Columns\TextColumn::make('name')->label('Nomor Meja')->sortable()->searchable(),
                FilamentTables\Columns\TextColumn::make('zone')
                    ->label('Zona')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),
                FilamentTables\Columns\TextColumn::make('pic_name')
                    ->label('PIC Area')
                    ->placeholder('-')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                FilamentTables\Columns\TextColumn::make('capacity')->label('Kapasitas')->alignCenter(),

                // Menghitung jumlah kursi terisi di memori (tanpa query database berulang)
                FilamentTables\Columns\TextColumn::make('terisi')
                    ->label('Terisi')
                    ->getStateUsing(fn (Table $record) => $record->getOccupiedSeats())
                    ->alignCenter(),

                // Menghitung sisa kursi
                FilamentTables\Columns\TextColumn::make('remaining')
                    ->label('Sisa Kursi')
                    ->getStateUsing(fn (Table $record) => $record->getRemainingSeats())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('event_id')
                    ->relationship('event', 'name')
                    ->label('Filter Acara')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                FilamentTables\Actions\EditAction::make(),
                FilamentTables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                FilamentTables\Actions\BulkActionGroup::make([
                    FilamentTables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTables::route('/'),
            'create' => Pages\CreateTable::route('/create'),
            'edit' => Pages\EditTable::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GuestsRelationManager::class,
        ];
    }
}
