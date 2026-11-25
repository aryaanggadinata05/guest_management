<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meja Registrasi & Scanner QR - {{ $settings->app_name ?? 'Guest Management' }}</title>
    @if($settings->getFaviconUrl())
        <link rel="icon" href="{{ $settings->getFaviconUrl() }}">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            primary: 'var(--brand-primary)',
                            secondary: 'var(--brand-secondary)',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <!-- Init Theme from localStorage immediately to prevent flicker -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('scan_theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <style>
        :root {
            --brand-primary: {{ $settings->primary_color ?? '#2f76b5' }};
            --brand-secondary: {{ $settings->secondary_color ?? '#1e4e79' }};
        }

        /* HTML5 QR Code Styling Overrides */
        #reader { width: 100% !important; border: none !important; }
        #reader video { border-radius: 0.75rem !important; max-height: 290px !important; object-fit: cover !important; margin: 0 auto; width: 100% !important; }
        #reader__dashboard { display: none !important; }
        #reader__status_span { display: none !important; }

        /* Viewfinder Scanner Styling */
        .viewfinder-box {
            position: relative;
            overflow: hidden;
            border-radius: 0.75rem;
        }

        .viewfinder-corner {
            position: absolute;
            width: 22px;
            height: 22px;
            border-color: var(--brand-primary);
            pointer-events: none;
            z-index: 10;
        }
        .corner-tl { top: 12px; left: 12px; border-top: 3.5px solid var(--brand-primary); border-left: 3.5px solid var(--brand-primary); border-top-left-radius: 6px; }
        .corner-tr { top: 12px; right: 12px; border-top: 3.5px solid var(--brand-primary); border-right: 3.5px solid var(--brand-primary); border-top-right-radius: 6px; }
        .corner-bl { bottom: 12px; left: 12px; border-bottom: 3.5px solid var(--brand-primary); border-left: 3.5px solid var(--brand-primary); border-bottom-left-radius: 6px; }
        .corner-br { bottom: 12px; right: 12px; border-bottom: 3.5px solid var(--brand-primary); border-right: 3.5px solid var(--brand-primary); border-bottom-right-radius: 6px; }

        /* Scanning Laser Line Animation */
        .scan-laser-line {
            position: absolute;
            left: 5%;
            width: 90%;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, var(--brand-primary) 50%, transparent 100%);
            box-shadow: 0 0 8px var(--brand-primary);
            animation: scanLaser 2.2s ease-in-out infinite alternate;
            z-index: 9;
            pointer-events: none;
        }

        @keyframes scanLaser {
            0% { top: 10%; opacity: 0.3; }
            50% { opacity: 0.95; }
            100% { top: 88%; opacity: 0.3; }
        }

        /* Dark Mode High-Contrast Ballroom Theme */
        html.dark body {
            background-color: #0b0f19 !important;
            color: #f8fafc !important;
        }
        html.dark .card-surface {
            background-color: #151e2e !important;
            border-color: #263346 !important;
        }
        html.dark .card-subsurface {
            background-color: #0d1522 !important;
            border-color: #1e293b !important;
        }
        html.dark .text-heading {
            color: #ffffff !important;
        }
        html.dark .text-muted {
            color: #94a3b8 !important;
        }
        html.dark .input-surface {
            background-color: #0b111c !important;
            border-color: #334155 !important;
            color: #ffffff !important;
        }
        html.dark .input-surface:focus {
            border-color: var(--brand-primary) !important;
        }

        /* Walk-In Button, Modal & Toast (Self-contained CSS) */
        .walkin-trigger-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            padding: 0.55rem 0.95rem;
            border-radius: 0.75rem;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            border: none;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.14);
            transition: transform 0.15s ease, opacity 0.15s ease;
            white-space: nowrap;
        }
        .walkin-trigger-btn:hover {
            opacity: 0.94;
            transform: translateY(-1px);
        }

        .walkin-modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.68);
            backdrop-filter: blur(5px);
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .walkin-modal-overlay.is-hidden {
            display: none !important;
        }
        .walkin-modal-card {
            width: 100%;
            max-width: 520px;
            max-height: 92vh;
            overflow-y: auto;
            background-color: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.35rem;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.28);
        }
        html.dark .walkin-modal-card {
            background-color: #151e2e;
            color: #f8fafc;
            border-color: #263346;
        }
        .walkin-alert {
            padding: 0.75rem 0.9rem;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.85rem;
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        html.dark .walkin-alert {
            background-color: rgba(127, 29, 29, 0.45);
            color: #fecaca;
            border-color: #991b1b;
        }
        .walkin-toast {
            position: fixed;
            bottom: 1.25rem;
            right: 1.25rem;
            z-index: 80;
            padding: 0.8rem 1.15rem;
            border-radius: 0.875rem;
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff;
            font-size: 0.8125rem;
            font-weight: 700;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.28);
            display: none;
            align-items: center;
            gap: 0.5rem;
            max-width: 90vw;
        }
        .walkin-toast.is-visible {
            display: flex;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen p-3 sm:p-5 md:p-6 transition-colors duration-200">
    <div class="max-w-6xl mx-auto space-y-4">

        {{-- 1. TOP HEADER BAR --}}
        <header class="card-surface bg-white p-3.5 sm:p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            {{-- Brand & Title --}}
            <div class="flex items-center gap-3">
                @if($settings->getLogoUrl())
                    <img src="{{ $settings->getLogoUrl() }}" alt="{{ $settings->app_name }}" class="h-10 max-w-[140px] object-contain">
                @else
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center font-black text-white text-base shadow-sm shrink-0" style="background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));">
                        {{ strtoupper(substr($settings->app_name ?? 'G', 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center flex-wrap gap-1.5 sm:gap-2">
                        <h1 class="text-base sm:text-lg font-black text-heading text-slate-900 tracking-tight leading-tight">
                            {{ $settings->app_name ?? 'Guest Management' }}
                        </h1>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shrink-0">
                            Meja Registrasi
                        </span>
                    </div>
                    <p class="text-xs text-muted text-slate-500 font-medium">Verifikasi kehadiran tamu dan ploting kursi real-time</p>
                </div>
            </div>

            {{-- Actions & User Badge --}}
            <div class="flex items-center flex-wrap gap-2 sm:gap-2.5">
                {{-- Logged-in Officer Badge --}}
                <div class="card-subsurface flex items-center gap-2 px-2.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="truncate max-w-[120px] sm:max-w-[160px]">{{ auth()->user()->name }}</span>
                    <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                        {{ auth()->user()->role }}
                    </span>
                </div>

                {{-- Ubah Password Button --}}
                <button type="button" onclick="openChangePasswordModal()" class="text-xs font-bold px-2.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-sm" title="Ubah Kata Sandi Akun">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <span class="hidden sm:inline">Ubah Password</span>
                </button>

                {{-- Dark / Light Mode Toggle --}}
                <button type="button" onclick="toggleTheme()" id="theme-toggle-btn" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 transition" title="Ganti Mode Gelap / Terang (Ballroom Mode)">
                    <!-- Sun icon (shown in dark mode) -->
                    <svg id="theme-sun-icon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon icon (shown in light mode) -->
                    <svg id="theme-moon-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                {{-- ROLE-GATED ADMIN BUTTON: Only visible to 'admin' --}}
                @if(auth()->user()->role === 'admin')
                    <a href="/" class="text-xs font-bold px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Panel Admin</span>
                    </a>
                @endif

                {{-- Logout Button --}}
                <form action="{{ route('logout.petugas') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 hover:bg-red-100 dark:hover:bg-red-900/50 px-3 py-2 rounded-xl border border-red-200 dark:border-red-900/50 transition flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </header>

        {{-- 2. TOP 4-CARD KPI STRIP --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            {{-- KPI 1: Acara Aktif Selector --}}
            <div class="card-surface bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-center">
                <span class="text-[10px] font-extrabold text-muted text-slate-400 uppercase tracking-wider">Acara Aktif</span>
                <select id="event-selector" onchange="handleEventChange(this.value)" class="input-surface mt-1.5 w-full text-xs font-bold rounded-xl border-slate-300 py-1.5 px-2.5 bg-slate-50 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    @foreach($events as $ev)
                        <option value="{{ $ev->id }}" {{ $activeEvent && $activeEvent->id == $ev->id ? 'selected' : '' }}>
                            {{ $ev->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- KPI 2: Tamu Terdaftar --}}
            <div class="card-surface bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-extrabold text-muted text-slate-400 uppercase tracking-wider">Tamu Terdaftar</span>
                    <h3 id="stat-total" class="text-xl sm:text-2xl font-black text-heading text-slate-800 leading-tight mt-0.5">0</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-base">
                    📋
                </div>
            </div>

            {{-- KPI 3: Sudah Check-In --}}
            <div class="card-surface bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-extrabold text-muted text-slate-400 uppercase tracking-wider">Sudah Check-In</span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <h3 id="stat-attended" class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">0</h3>
                        <span id="stat-unattended-badge" class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                            (<span id="stat-unattended">0</span> Belum)
                        </span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-base">
                    ✓
                </div>
            </div>

            {{-- KPI 4: Total Pax Masuk --}}
            <div class="card-surface bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-extrabold text-muted text-slate-400 uppercase tracking-wider">Total Masuk</span>
                    <h3 class="text-xs sm:text-sm font-bold text-blue-600 dark:text-blue-400 leading-tight mt-1.5">
                        <span id="stat-adults">0</span> Dewasa • <span id="stat-children">0</span> Anak
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-base">
                    👥
                </div>
            </div>
        </div>

        {{-- 3. SINGLE-FOCUS REGISTRATION WORKSPACE (CENTERED CARD, MAX-W 760PX) --}}
        <div class="max-w-[760px] mx-auto space-y-4">
            
            <div class="card-surface bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
                
                {{-- SEGMENTED TOGGLE CONTROL + WALK-IN BUTTON --}}
                <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
                    <div class="flex-1 bg-slate-100 dark:bg-slate-900/90 p-1 rounded-xl flex items-center border border-slate-200 dark:border-slate-800">
                        <button 
                            type="button" 
                            id="tab-btn-camera" 
                            onclick="switchScanMode('camera')"
                            class="flex-1 py-2 sm:py-2.5 px-3 rounded-lg text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition duration-150"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Kamera Scanner</span>
                        </button>
                        <button 
                            type="button" 
                            id="tab-btn-manual" 
                            onclick="switchScanMode('manual')"
                            class="flex-1 py-2 sm:py-2.5 px-3 rounded-lg text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition duration-150"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                            <span>Scanner Fisik / Cari Manual</span>
                        </button>
                    </div>

                    <button type="button" id="btn-open-walkin" onclick="openWalkInModal()" class="walkin-trigger-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ Tamu Walk-In</span>
                    </button>
                </div>

                {{-- PANEL 1: KAMERA SCANNER --}}
                <div id="panel-camera" class="space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Kamera Siap Memindai</span>
                        </span>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="restartScanner()" class="font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition text-[11px] flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Reset Kamera</span>
                            </button>
                        </div>
                    </div>

                    {{-- Camera Viewfinder Container --}}
                    <div class="viewfinder-box card-subsurface bg-slate-900 rounded-xl min-h-[260px] flex items-center justify-center border border-slate-300 dark:border-slate-700 shadow-inner">
                        <div class="viewfinder-corner corner-tl"></div>
                        <div class="viewfinder-corner corner-tr"></div>
                        <div class="viewfinder-corner corner-bl"></div>
                        <div class="viewfinder-corner corner-br"></div>
                        <div class="scan-laser-line"></div>

                        <div id="reader" class="z-0"></div>
                    </div>

                    <p class="text-[11px] text-muted text-slate-400 dark:text-slate-500 text-center">
                        Posisikan QR Code tamu ke dalam kotak pemindai. Kamera otomatis membaca dan memverifikasi.
                    </p>
                </div>

                {{-- PANEL 2: SCANNER FISIK / INPUT MANUAL --}}
                <div id="panel-manual" class="space-y-4 hidden">
                    <div>
                        <label for="manual-input" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Tembak Barcode Scanner atau Ketik Manual:
                        </label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    id="manual-input" 
                                    placeholder="Scan barcode QR atau ketik nama / nomor HP..." 
                                    class="input-surface w-full pl-9 pr-3 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none transition shadow-sm"
                                    onkeydown="if(event.key === 'Enter') handleManualSearch()"
                                >
                            </div>
                            <button 
                                type="button" 
                                onclick="handleManualSearch()" 
                                class="px-5 py-3 text-white font-bold text-xs sm:text-sm rounded-xl transition shadow-sm shrink-0 flex items-center gap-1.5"
                                style="background-color: var(--brand-primary);"
                            >
                                <span>Cari</span>
                            </button>
                        </div>
                    </div>

                    {{-- Search Results List --}}
                    <div id="search-results" class="space-y-1.5 max-h-56 overflow-y-auto"></div>

                    <div class="p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/40 rounded-xl text-xs text-blue-800 dark:text-blue-300 flex items-start gap-2">
                        <span class="text-base leading-none">💡</span>
                        <span>Mendukung barcode scanner gun USB/Bluetooth. Kolom input otomatis siap menerima data setelah modal check-in ditutup.</span>
                    </div>
                </div>

            </div>

            {{-- 4. RECENT CHECK-IN FEED (5 TAMU TERAKHIR) --}}
            <div class="card-surface bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black text-heading text-slate-800 uppercase tracking-wider">
                            5 Tamu Terakhir Check-in
                        </span>
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                    </div>
                    <span class="text-[11px] text-muted text-slate-400">Pembaruan Otomatis</span>
                </div>

                {{-- Feed Container --}}
                <div id="recent-feed-list" class="space-y-2">
                    <div class="text-center py-4 text-xs text-muted text-slate-400">
                        Memuat data riwayat check-in...
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- MODAL UBAH PASSWORD (POPUP KHUSUS PETUGAS / USER REGISTRASI) --}}
    <div id="changePasswordModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
        <div class="card-surface bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-5 sm:p-6 border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white" style="background-color: var(--brand-primary);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-heading text-slate-900 dark:text-white leading-tight">Ubah Kata Sandi</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Perbarui kata sandi akun Anda</p>
                    </div>
                </div>
                <button type="button" onclick="closeChangePasswordModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold text-2xl leading-none">&times;</button>
            </div>

            <div id="cp-modal-alert" class="mb-4 p-3 rounded-xl text-xs font-semibold hidden"></div>

            <form id="change-password-form" onsubmit="submitChangePassword(event)" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kata Sandi Saat Ini</label>
                    <input type="password" id="cp-current-password" required class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kata Sandi Baru</label>
                    <input type="password" id="cp-new-password" minlength="4" required class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Ulangi Kata Sandi Baru</label>
                    <input type="password" id="cp-new-password-confirmation" minlength="4" required class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" id="cp-submit-btn" class="flex-1 py-2.5 text-white font-bold text-xs rounded-xl transition shadow-md" style="background-color: var(--brand-primary);">
                        Simpan Kata Sandi
                    </button>
                    <button type="button" onclick="closeChangePasswordModal()" class="w-1/3 py-2.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL REGISTRASI TAMU WALK-IN ON-THE-SPOT --}}
    <div id="walkInModal" class="walkin-modal-overlay is-hidden">
        <div class="walkin-modal-card">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-heading leading-tight">Registrasi Tamu Walk-In</h3>
                    <p id="wi-event-label" class="text-xs text-muted text-slate-500">Daftarkan tamu baru, check-in otomatis & plot meja</p>
                </div>
                <button type="button" onclick="closeWalkInModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold text-2xl leading-none">&times;</button>
            </div>

            <div id="wi-modal-alert" class="walkin-alert hidden"></div>

            <form id="walkin-form" onsubmit="submitWalkIn(event)" class="space-y-3.5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="wi-name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Tamu <span class="text-red-500">*</span></label>
                        <input type="text" id="wi-name" required placeholder="Nama lengkap tamu..." class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:outline-none">
                    </div>
                    <div>
                        <label for="wi-phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nomor HP / WA (Opsional)</label>
                        <input type="text" id="wi-phone" placeholder="0812xxxx..." class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="wi-tenant-id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kategori / Pengundang</label>
                    <select id="wi-tenant-id" class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:outline-none">
                        <option value="">Tanpa Pengundang (Walk-In Umum)</option>
                    </select>
                </div>

                <div class="card-subsurface p-3.5 bg-slate-50 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3">
                    <span class="text-xs font-black text-heading block uppercase tracking-wider">Jumlah Hadir & Plot Meja</span>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="wi-adult-count" class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Dewasa</label>
                            <input type="number" id="wi-adult-count" min="1" max="50" value="1" required class="input-surface w-full border rounded-xl p-2 text-xs font-black">
                        </div>
                        <div>
                            <label for="wi-child-count" class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Anak</label>
                            <input type="number" id="wi-child-count" min="0" max="50" value="0" required class="input-surface w-full border rounded-xl p-2 text-xs font-black">
                        </div>
                    </div>

                    <div>
                        <label for="wi-table-id" class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Plot Nomor Meja</label>
                        <select id="wi-table-id" class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:outline-none">
                            <option value="">Tanpa Meja</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" id="wi-submit-btn" class="walkin-trigger-btn flex-1 !py-2.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Daftarkan &amp; Check-In</span>
                    </button>
                    <button type="button" onclick="closeWalkInModal()" class="w-1/3 py-2.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="walkin-toast" class="walkin-toast">
        <span>✓</span>
        <span id="walkin-toast-text"></span>
    </div>

    {{-- MODAL KONFIRMASI & UPDATE DATA TAMU (POPUP) --}}
    <div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
        <div class="card-surface bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-5 sm:p-6 max-h-[92vh] overflow-y-auto border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-200 dark:border-slate-700">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-heading text-slate-900 leading-tight">Konfirmasi Data Tamu</h3>
                    <p id="modal-tenant-name" class="text-xs text-muted text-slate-500"></p>
                </div>
                <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold text-2xl leading-none">&times;</button>
            </div>

            <div id="modal-alert" class="mb-4 p-3 rounded-xl text-xs font-semibold hidden"></div>

            <form id="checkin-form" onsubmit="submitCheckIn(event)" class="space-y-4">
                <input type="hidden" id="modal-guest-id">

                {{-- Profil Tamu (Dapat Dikoreksi) --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Tamu</label>
                        <input type="text" id="modal-name" required class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">No. Handphone / WA</label>
                        <input type="text" id="modal-phone" class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin</label>
                        <select id="modal-gender" class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="">Pilih Gender</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pekerjaan / Instansi</label>
                        <input type="text" id="modal-job-type" class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                {{-- Jumlah Orang Riil & Meja --}}
                <div class="card-subsurface p-4 bg-slate-50 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3">
                    <span class="text-xs font-black text-heading text-slate-700 block uppercase tracking-wider">Realisasi Kehadiran & Kursi</span>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Dewasa (Riil)</label>
                            <input type="number" id="modal-adult-count" min="1" required class="input-surface w-full border rounded-xl p-2 text-xs font-black">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Anak (Riil)</label>
                            <input type="number" id="modal-child-count" min="0" required class="input-surface w-full border rounded-xl p-2 text-xs font-black">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Pilihan Meja</label>
                        <select id="modal-table-id" class="input-surface w-full border rounded-xl p-2.5 text-xs font-semibold">
                            <option value="">Tanpa Meja</option>
                        </select>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="flex-1 py-3 text-white font-bold text-xs sm:text-sm rounded-xl transition shadow-md flex items-center justify-center gap-1.5" style="background-color: var(--brand-primary);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Konfirmasi & Check-In</span>
                    </button>
                    <button type="button" onclick="closeModal()" class="w-1/3 py-3 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm rounded-xl transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- JAVASCRIPT SCANNER, KAMERA, PREFERENSI, & AJAX --}}
    <script>
        const html5QrCode = new Html5Qrcode("reader");
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let isScannerPaused = false;
        let isScannerRunning = false;
        let currentMode = '{{ $preferredMode ?? "camera" }}';

        // 1. Theme Toggle
        function updateThemeIcon() {
            const isDark = document.documentElement.classList.contains('dark');
            const sunIcon = document.getElementById('theme-sun-icon');
            const moonIcon = document.getElementById('theme-moon-icon');
            if (isDark) {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            } else {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            }
        }

        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('scan_theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        }

        function getActiveEventId() {
            const sel = document.getElementById('event-selector');
            return sel ? sel.value : '';
        }

        // 2. Simpan Preferensi ke Database via AJAX
        function saveUserPreferences(prefs) {
            fetch('/scan/preferences', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(prefs)
            }).catch(err => console.warn('Gagal menyimpan preferensi:', err));
        }

        // 3. Segmented Mode Switcher (Kamera vs Manual)
        function switchScanMode(mode, saveToDb = true) {
            currentMode = mode;
            const btnCam = document.getElementById('tab-btn-camera');
            const btnMan = document.getElementById('tab-btn-manual');
            const panelCam = document.getElementById('panel-camera');
            const panelMan = document.getElementById('panel-manual');

            if (mode === 'camera') {
                // Style Active Camera Tab
                btnCam.style.backgroundColor = 'var(--brand-primary)';
                btnCam.style.color = '#ffffff';
                btnCam.className = "flex-1 py-2 sm:py-2.5 px-3 rounded-lg text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition duration-150 shadow-sm";
                
                // Style Inactive Manual Tab
                btnMan.style.backgroundColor = 'transparent';
                btnMan.style.color = '';
                btnMan.className = "flex-1 py-2 sm:py-2.5 px-3 rounded-lg text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 flex items-center justify-center gap-2 transition duration-150";

                panelCam.classList.remove('hidden');
                panelMan.classList.add('hidden');

                // Mulai Kamera
                startScanner();
            } else {
                // Style Active Manual Tab
                btnMan.style.backgroundColor = 'var(--brand-primary)';
                btnMan.style.color = '#ffffff';
                btnMan.className = "flex-1 py-2 sm:py-2.5 px-3 rounded-lg text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition duration-150 shadow-sm";
                
                // Style Inactive Camera Tab
                btnCam.style.backgroundColor = 'transparent';
                btnCam.style.color = '';
                btnCam.className = "flex-1 py-2 sm:py-2.5 px-3 rounded-lg text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 flex items-center justify-center gap-2 transition duration-150";

                panelCam.classList.add('hidden');
                panelMan.classList.remove('hidden');

                // Matikan Kamera untuk hemat baterai
                stopScanner();

                // Auto-focus input manual
                setTimeout(() => {
                    const input = document.getElementById('manual-input');
                    if (input) input.focus();
                }, 100);
            }

            if (saveToDb) {
                saveUserPreferences({ preferred_scan_mode: mode });
            }
        }

        // 4. Ganti Acara Aktif
        function handleEventChange(eventId) {
            loadStats();
            saveUserPreferences({ preferred_event_id: eventId });
        }

        // 5. Muat Statistik Terkini & Live Feed 5 Tamu Terakhir
        function applyStatsPayload(d) {
            if (!d) return;
            document.getElementById('stat-total').innerText = d.total || 0;
            document.getElementById('stat-attended').innerText = d.attended || 0;
            document.getElementById('stat-unattended').innerText = d.unattended || 0;
            document.getElementById('stat-adults').innerText = d.adults || 0;
            document.getElementById('stat-children').innerText = d.children || 0;

            // Render Feed 5 Tamu Terakhir
            const feedList = document.getElementById('recent-feed-list');
            if (!d.recent || d.recent.length === 0) {
                feedList.innerHTML = `
                    <div class="text-center py-4 text-xs text-muted text-slate-400">
                        Belum ada tamu yang check-in pada sesi acara ini.
                    </div>
                `;
            } else {
                feedList.innerHTML = d.recent.map(item => `
                    <div class="p-2.5 rounded-xl card-subsurface bg-slate-50 border border-slate-200 dark:border-slate-700/80 flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-white shrink-0 text-xs shadow-sm" style="background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));">
                                ${item.guest_name.charAt(0).toUpperCase()}
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-heading text-slate-800 dark:text-white truncate">${item.guest_name}</p>
                                <p class="text-[11px] text-muted text-slate-400 truncate">Pengundang: ${item.tenant_name || 'Tanpa Pengundang'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-200 dark:border-blue-900/50">
                                ${item.table_name}
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/50">
                                ${item.adult_count} Dewasa${item.child_count > 0 ? ', ' + item.child_count + ' Anak' : ''}
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium hidden sm:inline">
                                ${item.scanned_time}
                            </span>
                        </div>
                    </div>
                `).join('');
            }
        }

        function loadStats() {
            const eventId = getActiveEventId();
            if (!eventId) return;

            fetch(`/scan/stats?event_id=${eventId}`)
                .then(r => r.json())
                .then(d => applyStatsPayload(d))
                .catch(err => console.warn('Gagal memuat statistik:', err));
        }

        // 6. Kontrol Kamera (Start & Stop)
        function startScanner() {
            if (isScannerRunning) return;

            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                onScanSuccess
            ).then(() => {
                isScannerRunning = true;
                isScannerPaused = false;
            }).catch(err => {
                console.warn("Kamera tidak dapat diakses otomatis:", err);
                isScannerRunning = false;
            });
        }

        function stopScanner() {
            if (isScannerRunning) {
                html5QrCode.stop().then(() => {
                    isScannerRunning = false;
                    isScannerPaused = false;
                }).catch(() => {
                    isScannerRunning = false;
                });
            }
        }

        function restartScanner() {
            stopScanner();
            setTimeout(() => {
                startScanner();
            }, 300);
        }

        function onScanSuccess(decodedText) {
            if (isScannerPaused) return;
            isScannerPaused = true;
            fetchGuestData(decodedText);
        }

        // 7. Verifikasi Tamu & Tampilkan Modal
        function fetchGuestData(qrCode) {
            fetch('/scan/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ qr_code: qrCode })
            })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    playErrorBeep();
                    alert(res.message);
                    isScannerPaused = false;
                    return;
                }

                if (res.already_attended) {
                    playWarningBeep();
                } else {
                    playSuccessBeep();
                }

                openModal(res.guest, res.attendance, res.tables);
            })
            .catch(() => {
                playErrorBeep();
                alert('Gagal menghubungi server.');
                isScannerPaused = false;
            });
        }

        // 8. Buka Modal Konfirmasi Tamu
        function openModal(guest, attendance, tables) {
            document.getElementById('modal-guest-id').value = guest.id;
            document.getElementById('modal-name').value = guest.name || '';
            document.getElementById('modal-phone').value = guest.phone || '';
            document.getElementById('modal-gender').value = guest.gender || '';
            document.getElementById('modal-job-type').value = guest.job_type || '';
            document.getElementById('modal-tenant-name').innerText = `Tenant Pengundang: ${guest.tenant ? guest.tenant.name : 'Tanpa Pengundang'}`;

            // Setelan jumlah orang riil
            document.getElementById('modal-adult-count').value = attendance ? attendance.adult_count : (guest.adult_pax || 2);
            document.getElementById('modal-child-count').value = attendance ? attendance.child_count : (guest.child_pax || 0);

            // Tampilkan status jika sudah pernah check-in
            const alertBox = document.getElementById('modal-alert');
            if (guest.is_attended) {
                alertBox.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-900/60 block";
                alertBox.innerText = "⚠️ Tamu ini SUDAH CHECK-IN sebelumnya. Anda dapat memperbarui data atau alokasi meja.";
            } else {
                alertBox.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/60 block";
                alertBox.innerText = "🟢 Tamu belum check-in. Pastikan jumlah orang riil sudah benar.";
            }

            // Opsi Meja
            const tableSelect = document.getElementById('modal-table-id');
            tableSelect.innerHTML = '<option value="">Tanpa Meja</option>';
            tables.forEach(t => {
                const isSelected = guest.table_id === t.id ? 'selected' : '';
                tableSelect.innerHTML += `<option value="${t.id}" ${isSelected}>${t.name} (Sisa: ${t.remaining} Kursi / Kap: ${t.capacity})</option>`;
            });

            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            isScannerPaused = false;

            if (currentMode === 'manual') {
                const input = document.getElementById('manual-input');
                if (input) {
                    input.value = '';
                    input.focus();
                }
            }
        }

        // 9. Submit Check-In
        function submitCheckIn(e) {
            e.preventDefault();

            const payload = {
                guest_id: document.getElementById('modal-guest-id').value,
                name: document.getElementById('modal-name').value,
                phone: document.getElementById('modal-phone').value,
                gender: document.getElementById('modal-gender').value,
                job_type: document.getElementById('modal-job-type').value,
                adult_count: document.getElementById('modal-adult-count').value,
                child_count: document.getElementById('modal-child-count').value,
                table_id: document.getElementById('modal-table-id').value,
            };

            fetch('/scan/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    alert(res.message);
                    return;
                }
                closeModal();
                loadStats();
                const searchResults = document.getElementById('search-results');
                if (searchResults) searchResults.innerHTML = '';
            })
            .catch(() => alert('Gagal menyimpan kehadiran.'));
        }

        // 10. Pencarian Manual / Barcode Scanner Fisik
        function handleManualSearch() {
            const input = document.getElementById('manual-input');
            const keyword = input.value.trim();
            if (keyword.length < 2) return;

            fetch('/scan/search', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ keyword: keyword, event_id: getActiveEventId() })
            })
            .then(r => r.json())
            .then(res => {
                const resultsDiv = document.getElementById('search-results');
                resultsDiv.innerHTML = '';

                if (res.guests.length === 0) {
                    const safeKeyword = keyword.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    resultsDiv.innerHTML = `
                        <div class="p-3 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 text-center space-y-2">
                            <p class="text-xs text-red-500 font-semibold">Tamu tidak ditemukan pada acara ini.</p>
                            <button type="button" onclick="openWalkInModal('${safeKeyword}')" class="walkin-trigger-btn !text-xs !py-1.5 !px-3 mx-auto">
                                + Daftarkan sebagai Tamu Walk-In
                            </button>
                        </div>
                    `;
                    return;
                }

                // Jika hasil pencarian tepat 1, langsung buka modal
                if (res.guests.length === 1 && (res.guests[0].qr_code === keyword || res.guests[0].phone === keyword)) {
                    fetchGuestData(res.guests[0].qr_code);
                    return;
                }

                res.guests.forEach(g => {
                    const status = g.is_attended 
                        ? '<span class="text-[10px] bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 px-1.5 py-0.5 rounded font-bold">Hadir</span>'
                        : '<span class="text-[10px] bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-300 px-1.5 py-0.5 rounded">Belum</span>';
                    const tenantLabel = g.tenant ? g.tenant.name : 'Tanpa Pengundang';

                    resultsDiv.innerHTML += `
                        <div onclick="fetchGuestData('${g.qr_code}')" class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900/60 hover:bg-blue-50 dark:hover:bg-blue-950/40 cursor-pointer flex justify-between items-center transition">
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">${g.name} ${status}</p>
                                <p class="text-[11px] text-slate-400">Pengundang: ${tenantLabel} | HP: ${g.phone || '-'}</p>
                            </div>
                            <button type="button" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">Pilih</button>
                        </div>
                    `;
                });
            });
        }

        // 10b. Modal Registrasi Tamu Walk-In On-the-Spot
        function showWalkInToast(message) {
            const toast = document.getElementById('walkin-toast');
            const toastText = document.getElementById('walkin-toast-text');
            if (!toast || !toastText) return;
            toastText.innerText = message;
            toast.classList.add('is-visible');
            setTimeout(() => toast.classList.remove('is-visible'), 4000);
        }

        function openWalkInModal(prefillKeyword = '') {
            const eventSel = document.getElementById('event-selector');
            const eventId = getActiveEventId();
            if (!eventId) {
                alert('Tidak ada acara aktif yang tersedia.');
                return;
            }

            const eventName = eventSel && eventSel.selectedIndex >= 0
                ? eventSel.options[eventSel.selectedIndex].text
                : '';
            document.getElementById('wi-event-label').innerText = `Acara: ${eventName}`;

            const alertBox = document.getElementById('wi-modal-alert');
            alertBox.classList.add('hidden');
            alertBox.innerText = '';

            // Pre-fill nama atau nomor HP jika berasal dari pencarian nihil
            const cleanDigits = prefillKeyword.replace(/[^0-9+]/g, '');
            const isPhoneSearch = prefillKeyword && cleanDigits.length >= 6 && cleanDigits.length >= prefillKeyword.length * 0.7;
            document.getElementById('wi-name').value = isPhoneSearch ? '' : prefillKeyword;
            document.getElementById('wi-phone').value = isPhoneSearch ? prefillKeyword : '';
            document.getElementById('wi-adult-count').value = 1;
            document.getElementById('wi-child-count').value = 0;

            const tenantSelect = document.getElementById('wi-tenant-id');
            const tableSelect = document.getElementById('wi-table-id');
            tenantSelect.innerHTML = '<option value="">Tanpa Pengundang (Walk-In Umum)</option>';
            tableSelect.innerHTML = '<option value="">Tanpa Meja</option>';

            document.getElementById('walkInModal').classList.remove('is-hidden');

            fetch(`/scan/walk-in-options?event_id=${eventId}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;

                (res.tenants || []).forEach(t => {
                    tenantSelect.innerHTML += `<option value="${t.id}">${t.name} (Sisa Kuota: ${t.remaining_quota})</option>`;
                });

                (res.tables || []).forEach(tb => {
                    const zoneTxt = tb.zone ? ` (${tb.zone})` : '';
                    const fullTxt = tb.remaining <= 0 ? ' - PENUH' : ` - Sisa ${tb.remaining} Kursi`;
                    tableSelect.innerHTML += `<option value="${tb.id}">${tb.name}${zoneTxt}${fullTxt}</option>`;
                });
            })
            .catch(err => console.warn('Gagal memuat opsi walk-in:', err));
        }

        function closeWalkInModal() {
            document.getElementById('walkInModal').classList.add('is-hidden');
        }

        function submitWalkIn(e) {
            e.preventDefault();
            const alertBox = document.getElementById('wi-modal-alert');
            const submitBtn = document.getElementById('wi-submit-btn');

            const payload = {
                event_id: getActiveEventId(),
                name: document.getElementById('wi-name').value.trim(),
                phone: document.getElementById('wi-phone').value.trim() || null,
                tenant_id: document.getElementById('wi-tenant-id').value || null,
                adult_count: parseInt(document.getElementById('wi-adult-count').value || '1', 10),
                child_count: parseInt(document.getElementById('wi-child-count').value || '0', 10),
                table_id: document.getElementById('wi-table-id').value || null,
            };

            alertBox.classList.add('hidden');
            submitBtn.disabled = true;

            fetch('/scan/walk-in', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json().then(data => ({ status: r.status, data })))
            .then(res => {
                submitBtn.disabled = false;

                if (!res.data.success) {
                    playErrorBeep();
                    alertBox.innerText = res.data.message || 'Gagal mendaftarkan tamu walk-in.';
                    alertBox.classList.remove('hidden');
                    return;
                }

                playSuccessBeep();
                closeWalkInModal();
                applyStatsPayload(res.data.stats);

                const searchResults = document.getElementById('search-results');
                if (searchResults) searchResults.innerHTML = '';
                const manualInput = document.getElementById('manual-input');
                if (manualInput) manualInput.value = '';

                showWalkInToast(res.data.message);
            })
            .catch(() => {
                submitBtn.disabled = false;
                playErrorBeep();
                alertBox.innerText = 'Gagal terhubung ke server saat menyimpan tamu walk-in.';
                alertBox.classList.remove('hidden');
            });
        }

        // 11. Modal Ubah Password Petugas
        function openChangePasswordModal() {
            document.getElementById('cp-modal-alert').classList.add('hidden');
            document.getElementById('cp-current-password').value = '';
            document.getElementById('cp-new-password').value = '';
            document.getElementById('cp-new-password-confirmation').value = '';
            document.getElementById('changePasswordModal').classList.remove('hidden');
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').classList.add('hidden');
        }

        function submitChangePassword(e) {
            e.preventDefault();
            const alertBox = document.getElementById('cp-modal-alert');
            const submitBtn = document.getElementById('cp-submit-btn');

            const payload = {
                current_password: document.getElementById('cp-current-password').value,
                new_password: document.getElementById('cp-new-password').value,
                new_password_confirmation: document.getElementById('cp-new-password-confirmation').value,
            };

            if (payload.new_password !== payload.new_password_confirmation) {
                alertBox.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-red-50 text-red-700 border border-red-200 block";
                alertBox.innerText = "Konfirmasi kata sandi baru tidak cocok.";
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerText = "Menyimpan...";

            fetch('/scan/change-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json().then(data => ({ status: r.status, data })))
            .then(res => {
                submitBtn.disabled = false;
                submitBtn.innerText = "Simpan Kata Sandi";

                if (res.status === 200 && res.data.success) {
                    alert('Kata sandi berhasil diperbarui!');
                    closeChangePasswordModal();
                } else {
                    alertBox.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-red-50 text-red-700 border border-red-200 block";
                    alertBox.innerText = res.data.message || (res.data.errors ? Object.values(res.data.errors).flat().join(' ') : 'Gagal memperbarui kata sandi.');
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerText = "Simpan Kata Sandi";
                alertBox.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-red-50 text-red-700 border border-red-200 block";
                alertBox.innerText = "Gagal terhubung ke server.";
            });
        }

        // 12. Audio Feedback Synthesizer
        function playSuccessBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                osc.frequency.setValueAtTime(1046.5, ctx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.2);
            } catch(e) {}
        }

        function playWarningBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const playTone = (time, freq, dur) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(freq, time);
                    gain.gain.setValueAtTime(0.2, time);
                    gain.gain.exponentialRampToValueAtTime(0.01, time + dur);
                    osc.start(time);
                    osc.stop(time + dur);
                };
                playTone(ctx.currentTime, 440, 0.12);
                playTone(ctx.currentTime + 0.15, 330, 0.18);
            } catch(e) {}
        }

        function playErrorBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(220, ctx.currentTime);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.25);
            } catch(e) {}
        }

        // 13. Lifecycle Initialization
        document.addEventListener('DOMContentLoaded', () => {
            updateThemeIcon();

            // Set initial mode from user preference (without re-saving)
            switchScanMode(currentMode, false);

            // Muat data statistik & recent check-in
            setTimeout(() => {
                const activeEventId = getActiveEventId();
                if (activeEventId) {
                    loadStats();
                }
            }, 100);
        });
    </script>
</body>
</html>
