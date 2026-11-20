<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Pengaturan Tampilan';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Tampilan';

    protected static ?string $navigationGroup = 'Master & Pengaturan';

    protected static ?string $slug = 'pengaturan-tampilan';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.pages.manage-settings';

    public function getHeading(): string
    {
        return 'Pengaturan Tampilan';
    }

    public function getTitle(): string
    {
        return 'Pengaturan Tampilan';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $setting = Setting::query()->first() ?? Setting::defaultInstance();
        $this->form->fill($setting->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Pengaturan Tampilan & Branding')
                    ->tabs([
                        Tab::make('Identitas & Logo')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('app_name')
                                        ->label('Nama Aplikasi')
                                        ->required()
                                        ->placeholder('Contoh: Guest Management')
                                        ->helperText('Ditampilkan pada judul browser, header panel, dan layar login.'),

                                    TextInput::make('login_subtitle')
                                        ->label('Subjudul Layar Login')
                                        ->required()
                                        ->placeholder('Contoh: Sistem Registrasi & Manajemen Tamu Acara')
                                        ->helperText('Keterangan singkat yang muncul di bawah logo pada kartu login.'),
                                ]),

                                Grid::make(2)->schema([
                                    FileUpload::make('logo_path')
                                        ->label('Logo Resmi Aplikasi')
                                        ->disk('public')
                                        ->directory('branding')
                                        ->visibility('public')
                                        ->fetchFileInformation(false)
                                        ->getUploadedFileUsing(static fn (BaseFileUpload $component, string $file): ?array => [
                                            'name' => basename($file),
                                            'size' => 0,
                                            'type' => null,
                                            'url' => '/storage/'.ltrim($file, '/'),
                                        ])
                                        ->image()
                                        ->imageEditor()
                                        ->maxSize(2048)
                                        ->helperText('Format PNG/SVG/JPG (Maks. 2MB). Ditampilkan di header sidebar dan layar login.'),

                                    FileUpload::make('favicon_path')
                                        ->label('Favicon Tab Browser')
                                        ->disk('public')
                                        ->directory('branding')
                                        ->visibility('public')
                                        ->fetchFileInformation(false)
                                        ->getUploadedFileUsing(static fn (BaseFileUpload $component, string $file): ?array => [
                                            'name' => basename($file),
                                            'size' => 0,
                                            'type' => null,
                                            'url' => '/storage/'.ltrim($file, '/'),
                                        ])
                                        ->image()
                                        ->maxSize(1024)
                                        ->helperText('Ikon kecil tab browser (Format ICO/PNG, rasio 1:1, Maks. 1MB).'),
                                ]),
                            ]),

                        Tab::make('Tema & Warna')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Grid::make(2)->schema([
                                    ColorPicker::make('primary_color')
                                        ->label('Warna Primer (Primary)')
                                        ->required()
                                        ->helperText('Digunakan untuk tombol utama, sorotan aktif, dan link navigasi.'),

                                    ColorPicker::make('secondary_color')
                                        ->label('Warna Sekunder (Secondary)')
                                        ->required()
                                        ->helperText('Digunakan untuk aksen gradasi latar belakang login.'),
                                ]),

                                Actions::make([
                                    FormAction::make('preset_corporate')
                                        ->label('Corporate Blue (#2f76b5)')
                                        ->color('info')
                                        ->icon('heroicon-o-swatch')
                                        ->action(fn () => $this->applyPreset('#2f76b5', '#1e4e79', 'Corporate Blue')),

                                    FormAction::make('preset_emerald')
                                        ->label('Emerald VIP (#059669)')
                                        ->color('success')
                                        ->icon('heroicon-o-sparkles')
                                        ->action(fn () => $this->applyPreset('#059669', '#065f46', 'Emerald VIP')),

                                    FormAction::make('preset_gold')
                                        ->label('Royal Gold (#b45309)')
                                        ->color('warning')
                                        ->icon('heroicon-o-star')
                                        ->action(fn () => $this->applyPreset('#b45309', '#78350f', 'Royal Gold')),

                                    FormAction::make('preset_slate')
                                        ->label('Deep Slate (#334155)')
                                        ->color('gray')
                                        ->icon('heroicon-o-cube')
                                        ->action(fn () => $this->applyPreset('#334155', '#0f172a', 'Deep Slate')),
                                ])->columnSpanFull(),
                            ]),

                        Tab::make('Halaman Login')
                            ->icon('heroicon-o-arrow-right-on-rectangle')
                            ->schema([
                                Select::make('login_bg_type')
                                    ->label('Tipe Latar Belakang Login')
                                    ->options([
                                        'gradient' => 'Gradasi Halus Dinamis (Warna Primer & Sekunder)',
                                        'image' => 'Wallpaper Foto Kustom',
                                    ])
                                    ->required()
                                    ->live()
                                    ->helperText('Pilih apakah latar belakang login berupa gradasi warna dinamis atau wallpaper gambar.'),

                                FileUpload::make('login_bg_image')
                                    ->label('Berkas Wallpaper Layar Login')
                                    ->disk('public')
                                    ->directory('branding')
                                    ->visibility('public')
                                    ->fetchFileInformation(false)
                                    ->getUploadedFileUsing(static fn (BaseFileUpload $component, string $file): ?array => [
                                        'name' => basename($file),
                                        'size' => 0,
                                        'type' => null,
                                        'url' => '/storage/'.ltrim($file, '/'),
                                    ])
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(5120)
                                    ->visible(fn (Get $get): bool => $get('login_bg_type') === 'image')
                                    ->helperText('Gambar resolusi tinggi (1920x1080 disarankan, Maks. 5MB).'),

                                TextInput::make('login_overlay_opacity')
                                    ->label('Kepekatan Lapisan Peredup Wallpaper (%)')
                                    ->numeric()
                                    ->minValue(20)
                                    ->maxValue(80)
                                    ->default(45)
                                    ->required()
                                    ->suffix('%')
                                    ->visible(fn (Get $get): bool => $get('login_bg_type') === 'image')
                                    ->helperText('Mengatur tingkat kepekatan lapisan gelap (20% s.d. 80%) agar teks kartu login tetap terbaca jelas di atas foto.'),
                            ]),

                        Tab::make('Pengaturan WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Template & Metode Pengiriman')
                                    ->description('Atur pesan undangan WhatsApp dan metode pengiriman default ke tamu.')
                                    ->schema([
                                        Select::make('wa_default_method')
                                            ->label('Metode Pengiriman Utama')
                                            ->options([
                                                'wa_me' => '🟢 Tautan Langsung wa.me (Manual / Tanpa Biaya API)',
                                                'gateway' => '⚡ WhatsApp Gateway API (Otomatis & Bulk Broadcast)',
                                            ])
                                            ->default('wa_me')
                                            ->required()
                                            ->helperText('Metode pengiriman utama yang digunakan saat membagikan tiket QR ke tamu.'),

                                        Textarea::make('wa_message_template')
                                            ->label('Template Teks Pesan WhatsApp')
                                            ->rows(5)
                                            ->required()
                                            ->default(fn () => Setting::getDefaultWaTemplate())
                                            ->placeholder(Setting::getDefaultWaTemplate())
                                            ->helperText('Variabel dinamis: {nama}, {acara}, {lokasi}, {tanggal}, {meja}, {pax}, {dewasa}, {anak}, {link_tiket}, {qr_code}, {pengundang}.'),
                                    ]),

                                Section::make('Konfigurasi WhatsApp Gateway (Broadcast Otomatis)')
                                    ->description('Hubungkan gateway pihak ketiga (Fonnte / Wablas / Custom) untuk fitur kirim otomatis dan broadcast massal.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('wa_gateway_provider')
                                                ->label('Penyedia Layanan (Provider)')
                                                ->options([
                                                    'fonnte' => 'Fonnte (api.fonnte.com - Rekomendasi)',
                                                    'wablas' => 'Wablas (phone.wablas.com)',
                                                    'generic' => 'Kustom / Generic HTTP API',
                                                ])
                                                ->default('fonnte')
                                                ->live()
                                                ->required(),

                                            TextInput::make('wa_gateway_api_key')
                                                ->label('API Key / Token Gateway')
                                                ->password()
                                                ->revealable()
                                                ->placeholder('Masukkan token API Anda')
                                                ->helperText('Token otentikasi dari dashboard provider WhatsApp gateway.'),
                                        ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('wa_gateway_endpoint')
                                                ->label('URL Endpoint Kustom')
                                                ->placeholder('https://api.fonnte.com/send')
                                                ->helperText('Isi jika menggunakan Wablas domain kustom atau provider generic.')
                                                ->visible(fn (Get $get): bool => in_array($get('wa_gateway_provider'), ['wablas', 'generic'])),

                                            TextInput::make('wa_gateway_sender')
                                                ->label('Nomor Pengirim / Device ID (Opsional)')
                                                ->placeholder('Contoh: 6281234567890'),
                                        ]),

                                        Actions::make([
                                            FormAction::make('test_gateway')
                                                ->label('Uji Coba Kirim Pesan Gateway')
                                                ->icon('heroicon-o-paper-airplane')
                                                ->color('success')
                                                ->form([
                                                    TextInput::make('target_phone')
                                                        ->label('Nomor WhatsApp Tujuan Uji Coba')
                                                        ->placeholder('Contoh: 081234567890')
                                                        ->tel()
                                                        ->required()
                                                        ->helperText('Nomor akan otomatis diformat ke format internasional (+62).'),
                                                ])
                                                ->action(function (array $data): void {
                                                    $setting = Setting::query()->first() ?? new Setting;
                                                    $formState = $this->form->getState();
                                                    $setting->fill($formState);
                                                    $setting->save();
                                                    Cache::forget('app_settings');

                                                    $result = WhatsAppService::sendTestMessage($data['target_phone']);

                                                    if ($result['success']) {
                                                        Notification::make()
                                                            ->title('Uji Coba Berhasil!')
                                                            ->body($result['message'])
                                                            ->success()
                                                            ->send();
                                                    } else {
                                                        Notification::make()
                                                            ->title('Uji Coba Gagal')
                                                            ->body($result['message'])
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),
                                        ]),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function applyPreset(string $primary, string $secondary, string $name): void
    {
        $setting = Setting::query()->first() ?? new Setting;
        $setting->primary_color = $primary;
        $setting->secondary_color = $secondary;
        $setting->save();

        Cache::forget('app_settings');

        Notification::make()
            ->title("Preset {$name} Diterapkan")
            ->body("Warna tema berhasil diperbarui ke {$name}.")
            ->success()
            ->send();

        $this->redirect(static::getUrl(), navigate: false);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Pastikan file path selalu disimpan sebagai string bersih, bukan array
        foreach (['logo_path', 'favicon_path', 'login_bg_image'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_values($data[$field])[0] ?? null;
            }
        }

        $setting = Setting::query()->first() ?? new Setting;
        $setting->fill($data);
        $setting->save();

        Cache::forget('app_settings');

        Notification::make()
            ->title('Pengaturan Tampilan Disimpan')
            ->body('Konfigurasi identitas, tema warna, dan tampilan login berhasil disimpan.')
            ->success()
            ->send();

        $this->redirect(static::getUrl(), navigate: false);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->submit('save')
                ->color('primary')
                ->icon('heroicon-o-check'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }
}
