<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Acara (Events)';

    protected static ?string $navigationGroup = 'Master & Pengaturan';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Acara';

    protected static ?string $pluralModelLabel = 'Daftar Acara';

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->label('Nama Acara')->maxLength(255),
                TextInput::make('location')->required()->label('Lokasi'),
                DatePicker::make('start_date')->required()->label('Tanggal Mulai'),
                DatePicker::make('end_date')->label('Tanggal Selesai (Opsional)'),
                Toggle::make('is_active')->default(true)->label('Status Aktif'),
                FileUpload::make('floor_plan_image')
                    ->label('Denah Ruangan (Floor Plan)')
                    ->image()
                    ->disk('public')
                    ->directory('floor-plans')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->getUploadedFileUsing(static function (BaseFileUpload $component, string $file): ?array {
                        $path = ltrim($file, '/');

                        if (! Storage::disk('public')->exists($path)) {
                            return null;
                        }

                        return [
                            'name' => basename($path),
                            'size' => 0,
                            'type' => null,
                            'url' => '/storage/'.$path,
                        ];
                    })
                    ->helperText('Unggah gambar denah venue/ballroom (PNG, JPG, WebP) sebagai latar belakang pemetaan meja.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->label('Nama Acara'),
                TextColumn::make('location')->label('Lokasi'),
                TextColumn::make('start_date')->date()->sortable()->label('Mulai'),
                TextColumn::make('end_date')->date()->label('Selesai'),
                IconColumn::make('is_active')->boolean()->label('Aktif'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download_report')
                    ->label('Laporan Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (Event $record): string => route('report.download', ['event_id' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TenantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
