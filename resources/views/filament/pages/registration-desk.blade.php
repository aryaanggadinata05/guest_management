<x-filament-panels::page>
    @push('styles')
        <style>
            /* Merapikan tampilan video kamera html5-qrcode agar pas & tidak meluber */
            #reader {
                width: 100% !important;
                border: none !important;
                background: transparent !important;
            }
            #reader video {
                border-radius: 0.75rem !important;
                max-height: 260px !important;
                width: 100% !important;
                object-fit: cover !important;
                margin: 0 auto;
            }
            #reader__scan_region {
                background: transparent !important;
            }
            #reader__dashboard {
                display: none !important; /* Sembunyikan kontrol bawaan library yang mengganggu */
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode"></script>
    @endpush

    {{-- 1. KARTU STATISTIK & EVENT: MODEL COMPACT HORIZONTAL --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        {{-- Acara Aktif --}}
        <div class="py-2.5 px-3.5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col justify-center">
            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Acara Aktif</span>
            <select wire:model.live="selected_event_id" class="mt-1 w-full text-xs font-semibold rounded-lg border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white py-1 px-2 focus:ring-primary-500 focus:border-primary-500">
                @foreach(\App\Models\Event::where('is_active', true)->pluck('name', 'id') as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Tamu Terdaftar --}}
        <div class="py-2.5 px-3.5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Tamu Terdaftar</span>
                <h4 class="text-xl font-extrabold text-gray-900 dark:text-white mt-0.5 leading-tight">{{ $this->summary['total'] }}</h4>
            </div>
            <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-users" class="w-5 h-5" />
            </div>
        </div>

        {{-- Sudah Check-In --}}
        <div class="py-2.5 px-3.5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Sudah Check-In</span>
                <h4 class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5 leading-tight">
                    {{ $this->summary['attended'] }} 
                    <span class="text-xs font-normal text-gray-400">({{ $this->summary['unattended'] }} Belum)</span>
                </h4>
            </div>
            <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg text-emerald-600">
                <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5" />
            </div>
        </div>

        {{-- Total Orang Masuk --}}
        <div class="py-2.5 px-3.5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Masuk</span>
                <h4 class="text-sm font-bold text-primary-600 dark:text-primary-400 mt-1 leading-tight">
                    {{ $this->summary['adults'] }} <span class="text-xs font-normal text-gray-500">Dewasa</span> • 
                    {{ $this->summary['children'] }} <span class="text-xs font-normal text-gray-500">Anak</span>
                </h4>
            </div>
            <div class="p-2 bg-primary-50 dark:bg-primary-950/40 rounded-lg text-primary-600">
                <x-filament::icon icon="heroicon-o-user-group" class="w-5 h-5" />
            </div>
        </div>
    </div>

    {{-- 2. DUA MODA SCANNER: KAMERA (AUTO-AKTIF) & SCANNER MANUAL --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
        
        {{-- KOLOM 1: KAMERA SCANNER (OTOMATIS MENYALA SEPERTI MENU SCAN LAMA) --}}
        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5" id="camera-status-dot">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white" id="camera-title-text">Kamera Scanner Aktif</h3>
                    </div>
                    
                    {{-- Tombol Beralih / Matikan Kamera --}}
                    <div class="flex gap-1.5">
                        <button 
                            id="btn-toggle-cam" 
                            type="button" 
                            onclick="toggleCamera()" 
                            class="px-2.5 py-1 text-[11px] font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md transition"
                        >
                            Matikan
                        </button>
                    </div>
                </div>

                {{-- Area Layar Kamera --}}
                <div class="relative w-full rounded-xl overflow-hidden bg-black/5 dark:bg-black/30 border border-gray-200 dark:border-gray-700 min-h-[220px] max-h-[260px] flex items-center justify-center">
                    <div id="reader"></div>
                    <div id="camera-placeholder" class="hidden text-center p-6 space-y-2">
                        <x-filament::icon icon="heroicon-o-video-camera-slash" class="w-8 h-8 text-gray-400 mx-auto" />
                        <p class="text-xs text-gray-500" id="camera-msg">Kamera nonaktif.</p>
                        <button type="button" onclick="startScanner()" class="mt-2 text-xs font-semibold px-3 py-1.5 bg-emerald-600 text-white rounded-md shadow-sm">
                            Nyalakan Kamera
                        </button>
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-gray-400 text-center mt-3">
                Arahkan QR Code tamu langsung ke layar kamera untuk memindai otomatis.
            </p>
        </div>

        {{-- KOLOM 2: SCANNER FISIK / KETIK MANUAL --}}
        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <x-filament::icon icon="heroicon-o-bars-3-bottom-left" class="w-4 h-4 text-primary-500" />
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Alat Scanner / Ketik Manual</h3>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Gunakan scanner fisik (USB/Bluetooth) atau ketik Nama / No. HP tamu di bawah lalu tekan <kbd class="px-1.5 py-0.5 text-[10px] font-semibold bg-gray-100 border border-gray-300 rounded dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">Enter</kbd>[cite: 36].
                </p>

                <form wire:submit.prevent="processScan()" class="space-y-3">
                    <div class="relative">
                        <input 
                            id="scanInput"
                            type="text" 
                            wire:model="search_query"
                            autofocus 
                            autocomplete="off"
                            placeholder="Klik di sini lalu scan barcode / ketik nama..." 
                            class="w-full text-center text-base font-mono py-3.5 px-4 rounded-xl border-2 border-primary-500 focus:ring-4 focus:ring-primary-500/20 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none transition-all shadow-sm"
                        />
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        Cari Tamu / Proses Scan
                    </button>
                </form>
            </div>

            <div class="p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-800/40 rounded-xl mt-4">
                <p class="text-[11px] text-amber-700 dark:text-amber-300 flex items-center gap-1.5 font-medium">
                    <x-filament::icon icon="heroicon-m-light-bulb" class="w-4 h-4 text-amber-500 flex-shrink-0" />
                    Kamera HP/Webcam dan scanner fisik dapat digunakan bersamaan kapan saja.
                </p>
            </div>
        </div>

    </div>

    {{-- SCRIPT KAMERA OTOMATIS & PENGENDALIAN EVENT MODAL --}}
    <script>
        let html5QrCode = null;
        let isCameraRunning = false;
        let isScanningLocked = false;

        function updateUIState(active, message = "") {
            const dot = document.getElementById('camera-status-dot');
            const title = document.getElementById('camera-title-text');
            const btn = document.getElementById('btn-toggle-cam');
            const placeholder = document.getElementById('camera-placeholder');
            const msgEl = document.getElementById('camera-msg');

            if (active) {
                if (dot) dot.classList.remove('hidden');
                if (title) title.innerText = "Kamera Scanner Aktif";
                if (btn) btn.innerText = "Matikan";
                if (placeholder) placeholder.classList.add('hidden');
            } else {
                if (dot) dot.classList.add('hidden');
                if (title) title.innerText = "Kamera Nonaktif";
                if (btn) btn.innerText = "Nyalakan";
                if (placeholder) placeholder.classList.remove('hidden');
                if (msgEl && message) msgEl.innerText = message;
            }
        }

        function startScanner() {
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("reader");
            }

            // Utamakan kamera belakang pada HP (environment), atau kamera default webcam pada PC
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 220, height: 220 },
                    aspectRatio: 1.0
                },
                (decodedText) => {
                    if (isScanningLocked) return;

                    isScanningLocked = true; // Kunci pemindaian agar tidak dobel scan
                    playBeepSound();

                    // Kirim ke method Livewire
                    @this.processScan(decodedText);
                },
                (errorMessage) => {}
            ).then(() => {
                isCameraRunning = true;
                updateUIState(true);
            }).catch(err => {
                console.warn("Gagal auto-start kamera:", err);
                isCameraRunning = false;
                updateUIState(false, "Kamera tidak terdeteksi atau izin belum diberikan.");
            });
        }

        function stopScanner() {
            if (html5QrCode && isCameraRunning) {
                html5QrCode.stop().then(() => {
                    isCameraRunning = false;
                    updateUIState(false);
                }).catch(err => console.error(err));
            }
        }

        function toggleCamera() {
            if (isCameraRunning) {
                stopScanner();
            } else {
                startScanner();
            }
        }

        function playBeepSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = 880;
                gain.gain.value = 0.1;
                osc.start();
                setTimeout(() => {
                    osc.stop();
                    ctx.close();
                }, 150);
            } catch (e) {}
        }

        document.addEventListener('livewire:initialized', () => {
            const manualInput = document.getElementById('scanInput');
            if (manualInput) manualInput.focus();

            // OTOMATIS NYALAKAN KAMERA SAAT HALAMAN DIMUAT
            setTimeout(() => {
                startScanner();
            }, 500);

            // Buka kunci kamera dan autofocus input keyboard saat modal ditutup
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    setTimeout(() => {
                        const activeModal = document.querySelector('.fi-modal');
                        if (!activeModal) {
                            isScanningLocked = false;
                            if (manualInput) manualInput.focus();
                        }
                    }, 350);
                });
            });
        });
    </script>

    {{-- Render Action Modals Filament --}}
    <x-filament-actions::modals />
</x-filament-panels::page>