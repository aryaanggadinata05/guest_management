<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Manajemen Pengguna';

    protected static ?string $navigationGroup = 'Master & Pengaturan';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Daftar Pengguna';

    protected static ?int $navigationSort = 30;

    // BATASI HANYA UNTUK ADMIN
    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Akun')->schema([
                    TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('username') // Sesuaikan dengan nama kolom di database Anda
                        ->label('Nama Pengguna (Username)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('password')
                        ->label('Kata Sandi')
                        ->password()
                        ->revealable() // Fitur untuk melihat isi password
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state)) // Otomatis hash saat disimpan
                        ->dehydrated(fn ($state) => filled($state)) // Hanya simpan jika input tidak kosong
                        ->required(fn (string $context): bool => $context === 'create') // Wajib saat tambah baru, opsional saat edit
                        ->maxLength(255),
                ])->columns(2),

                Section::make('Hak Akses & Status')->schema([
                    Select::make('role')
                        ->label('Peran Akun')
                        ->options([
                            'admin' => 'Administrator',
                            'tenant' => 'Tenant / Pengundang',
                            'register' => 'Petugas Scanner',
                        ])
                        ->required()
                        ->live()
                        ->default('register'),

                    Select::make('tenant_id')
                        ->label('Asal Tenant')
                        ->relationship('tenant', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Forms\Get $get) => $get('role') === 'tenant')
                        ->required(fn (Forms\Get $get) => $get('role') === 'tenant'),

                    Toggle::make('is_active')
                        ->label('Status Aktif')
                        ->default(true)
                        ->helperText('Jika dinonaktifkan, user tidak dapat login atau mengakses sistem.')
                        ->disabled(fn (?User $record) => $record && $record->id === auth()->id()),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('role')
                    ->label('Peran (Role)')
                    ->badge() // Membuat tampilan menyerupai tag/pil
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'tenant' => 'info',
                        'register' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Administrator',
                        'tenant' => 'Tenant',
                        'register' => 'Petugas Scanner',
                        default => ucfirst($state),
                    })
                    ->searchable(),

                TextColumn::make('tenant.name')
                    ->label('Asal Tenant')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),

                ToggleColumn::make('is_active')
                    ->label('Status Aktif')
                    ->disabled(fn (User $record): bool => $record->id === auth()->id())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Didaftarkan Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter berdasarkan Role
                SelectFilter::make('role')
                    ->label('Filter Peran')
                    ->options([
                        'admin' => 'Administrator',
                        'tenant' => 'Tenant',
                        'register' => 'Petugas Scanner',
                    ]),

                // Filter berdasarkan Status Aktif
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua Status')
                    ->trueLabel('Hanya User Aktif')
                    ->falseLabel('Hanya User Non-Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    // Cegah admin menghapus dirinya sendiri
                    ->hidden(fn (User $record) => $record->id === auth()->id()),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
