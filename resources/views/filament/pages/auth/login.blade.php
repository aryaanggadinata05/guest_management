@php
    $settings = \App\Models\Setting::get();
    $logoUrl = $settings->getLogoUrl();
    $bgImageUrl = $settings->getLoginBgUrl();
    $isImageBg = $settings->login_bg_type === 'image' && filled($bgImageUrl);
    $overlayOpacity = ($settings->login_overlay_opacity ?? 45) / 100;
@endphp

<div 
    class="gm-login-viewport"
    style="
        @if($isImageBg)
            background-image: url('{{ $bgImageUrl }}') !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
        @else
            background: linear-gradient(135deg, var(--brand-primary, {{ $settings->primary_color }}) 0%, var(--brand-secondary, {{ $settings->secondary_color }}) 100%) !important;
        @endif
    "
>
    <style>
        .gm-login-viewport {
            min-height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }

        .gm-login-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
        }

        .gm-orb-1 {
            position: absolute;
            top: -120px;
            left: -120px;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 70%);
            filter: blur(40px);
            pointer-events: none;
            z-index: 1;
        }

        .gm-orb-2 {
            position: absolute;
            bottom: -140px;
            right: -140px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 0, 0, 0.28) 0%, rgba(0, 0, 0, 0) 70%);
            filter: blur(50px);
            pointer-events: none;
            z-index: 1;
        }

        .gm-login-card-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .gm-login-card {
            width: 100%;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.5);
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        html.dark .gm-login-card,
        .dark .gm-login-card {
            background: rgba(15, 23, 42, 0.92);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.12);
            color: #f8fafc;
        }

        .gm-login-logo {
            max-height: 64px;
            width: auto;
            max-width: 220px;
            margin: 0 auto 1.25rem;
            display: block;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .gm-logo-badge {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--brand-primary, {{ $settings->primary_color }}), var(--brand-secondary, {{ $settings->secondary_color }}));
            color: #ffffff;
            font-size: 1.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .gm-login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 0.25rem 0;
            text-align: center;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }

        html.dark .gm-login-title,
        .dark .gm-login-title {
            color: #f8fafc;
        }

        .gm-login-subtitle {
            font-size: 0.875rem;
            color: #475569;
            margin: 0 0 1.75rem 0;
            text-align: center;
            line-height: 1.45;
        }

        html.dark .gm-login-subtitle,
        .dark .gm-login-subtitle {
            color: #cbd5e1;
        }

        /* Enforce clear form labels contrast */
        .gm-login-card .fi-fo-field-wrp-label span {
            color: #1e293b !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
        }

        html.dark .gm-login-card .fi-fo-field-wrp-label span,
        .dark .gm-login-card .fi-fo-field-wrp-label span {
            color: #f1f5f9 !important;
        }

        .gm-login-card label,
        .gm-login-card .fi-checkbox-label {
            color: #334155 !important;
            font-size: 0.875rem !important;
        }

        html.dark .gm-login-card label,
        html.dark .gm-login-card .fi-checkbox-label,
        .dark .gm-login-card label,
        .dark .gm-login-card .fi-checkbox-label {
            color: #cbd5e1 !important;
        }

        /* Submit Button Styling */
        .gm-login-card .fi-btn-color-primary,
        .gm-login-card button[type="submit"] {
            width: 100% !important;
            padding: 0.75rem 1rem !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            background: linear-gradient(135deg, var(--brand-primary, {{ $settings->primary_color }}), var(--brand-secondary, {{ $settings->secondary_color }})) !important;
            color: #ffffff !important;
            border: none !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2) !important;
            transition: opacity 0.2s ease, transform 0.1s ease !important;
            cursor: pointer !important;
            margin-top: 0.5rem !important;
        }

        .gm-login-card .fi-btn-color-primary:hover,
        .gm-login-card button[type="submit"]:hover {
            opacity: 0.94 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25) !important;
        }

        .gm-login-card .fi-btn-color-primary span,
        .gm-login-card button[type="submit"] span {
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        /* Footer Copyright */
        .gm-login-footer {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.8125rem;
            margin-top: 1.5rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
            text-align: center;
            font-weight: 500;
            z-index: 10;
            position: relative;
        }
    </style>

    {{-- Overlay Background jika menggunakan gambar --}}
    @if($isImageBg)
        <div class="gm-login-overlay" style="background: rgba(15, 23, 42, {{ $overlayOpacity }}) !important;"></div>
    @else
        <div class="gm-orb-1"></div>
        <div class="gm-orb-2"></div>
    @endif

    {{-- Kartu Login Glassmorphic --}}
    <div class="gm-login-card-container">
        <div class="gm-login-card">
            {{-- Header Logo & Identitas --}}
            @if($logoUrl)
                <img 
                    src="{{ $logoUrl }}" 
                    alt="{{ $settings->app_name }}" 
                    class="gm-login-logo"
                />
            @else
                <div class="gm-logo-badge">
                    {{ strtoupper(substr($settings->app_name ?: 'G', 0, 1)) }}
                </div>
            @endif

            <h1 class="gm-login-title">
                {{ $settings->app_name ?: 'Guest Management' }}
            </h1>
            
            <p class="gm-login-subtitle">
                {{ $settings->login_subtitle ?: 'Sistem Registrasi & Manajemen Tamu Acara' }}
            </p>

            {{-- Formulir Login Filament --}}
            <x-filament-panels::form id="form" wire:submit="authenticate">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="true"
                    class="mt-4"
                />
            </x-filament-panels::form>
        </div>

        {{-- Footer Hak Cipta --}}
        <p class="gm-login-footer">
            &copy; {{ date('Y') }} {{ $settings->app_name ?: 'Guest Management' }}. Hak Cipta Dilindungi.
        </p>
    </div>
</div>
