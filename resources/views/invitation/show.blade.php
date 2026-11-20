<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-Tiket Undangan: {{ $guest->name }} - {{ $guest->event?->name ?? 'Acara' }}</title>
    
    @if($settings->getFaviconUrl())
        <link rel="icon" href="{{ $settings->getFaviconUrl() }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: {{ $settings->primary_color ?? '#2f76b5' }};
            --brand-secondary: {{ $settings->secondary_color ?? '#1e4e79' }};
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.25rem 1rem;
            color: #1e293b;
        }

        .ticket-wrapper {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .ticket-card {
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.2);
            position: relative;
        }

        /* Top Header Brand */
        .ticket-header {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            padding: 1.75rem 1.5rem 1.5rem;
            text-align: center;
            color: #ffffff;
            position: relative;
        }

        .ticket-logo {
            max-height: 48px;
            max-width: 160px;
            object-fit: contain;
            margin: 0 auto 0.75rem;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
        }

        .event-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .event-title {
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: -0.01em;
        }

        .event-meta {
            font-size: 0.8125rem;
            opacity: 0.9;
            margin-top: 0.35rem;
        }

        /* Body Section */
        .ticket-body {
            padding: 1.75rem 1.5rem 1.5rem;
            text-align: center;
        }

        .guest-greeting {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .guest-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0.25rem 0 0.75rem;
            line-height: 1.3;
        }

        /* Table & Pax Badges */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .detail-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 0.5rem;
        }

        .detail-box.primary {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .detail-label {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .detail-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0.15rem;
        }

        .detail-box.primary .detail-value {
            color: var(--brand-primary);
        }

        /* Tear line */
        .tear-line {
            position: relative;
            height: 20px;
            margin: 0.5rem -1.5rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tear-line::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            border-top: 2px dashed #cbd5e1;
        }

        .notch-left, .notch-right {
            position: absolute;
            width: 20px;
            height: 20px;
            background: var(--brand-secondary);
            border-radius: 50%;
            top: 0;
            z-index: 2;
        }

        .notch-left { left: -10px; }
        .notch-right { right: -10px; }

        /* QR Code Container */
        .qr-wrapper {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 1rem;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 0.75rem;
        }

        .qr-wrapper svg {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
        }

        .qr-hint {
            font-size: 0.8125rem;
            color: #475569;
            font-weight: 500;
            line-height: 1.4;
            max-width: 280px;
            margin: 0 auto 1.25rem;
        }

        .qr-hint strong {
            color: #0f172a;
        }

        /* Check-in status */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            margin-bottom: 0.5rem;
        }

        .status-badge.attended {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.pending {
            background: #f1f5f9;
            color: #475569;
        }

        /* Actions */
        .ticket-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        /* Footer */
        .ticket-footer {
            margin-top: 1.25rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="ticket-wrapper">
        <div class="ticket-card">
            {{-- Header Acara --}}
            <div class="ticket-header">
                @if($settings->getLogoUrl())
                    <img src="{{ $settings->getLogoUrl() }}" alt="{{ $settings->app_name }}" class="ticket-logo">
                @endif

                <div class="event-badge">Tiket Registrasi Masuk</div>
                <h1 class="event-title">{{ $guest->event?->name ?? 'Acara Spesial' }}</h1>
                
                @if($guest->event?->start_date || $guest->event?->location)
                    <p class="event-meta">
                        @if($guest->event?->start_date)
                            📅 {{ date('d F Y', strtotime($guest->event->start_date)) }}
                        @endif
                        @if($guest->event?->location)
                            &nbsp;•&nbsp; 📍 {{ $guest->event->location }}
                        @endif
                    </p>
                @endif
            </div>

            {{-- Isi Tiket --}}
            <div class="ticket-body">
                <p class="guest-greeting">Nama Tamu Undangan</p>
                <h2 class="guest-name">{{ $guest->name }}</h2>

                @if($guest->is_attended)
                    <div class="status-badge attended">
                        ✓ Sudah Hadir (Checked-in)
                    </div>
                @else
                    <div class="status-badge pending">
                        • Menunggu Kehadiran
                    </div>
                @endif

                {{-- Detail Meja & Pax --}}
                <div class="details-grid">
                    <div class="detail-box primary">
                        <div class="detail-label">Plot Meja</div>
                        <div class="detail-value">
                            @if($guest->table)
                                {{ $guest->table->name }}
                            @else
                                Sesuai Panitia
                            @endif
                        </div>
                    </div>

                    <div class="detail-box">
                        <div class="detail-label">Jumlah Pax</div>
                        <div class="detail-value">
                            {{ ($guest->adult_pax ?? 0) + ($guest->child_pax ?? 0) }} Orang
                        </div>
                    </div>
                </div>

                {{-- Garis Sobekan Tiket --}}
                <div class="tear-line">
                    <div class="notch-left"></div>
                    <div class="notch-right"></div>
                </div>

                {{-- Tampilan QR Code --}}
                <div class="qr-wrapper">
                    {!! $qrCodeSvg !!}
                </div>

                <p class="qr-hint">
                    Tunjukkan <strong>QR Code</strong> di atas kepada petugas saat tiba di meja registrasi.
                </p>

                {{-- Tombol Tindakan --}}
                <div class="ticket-actions">
                    <button type="button" class="btn-action btn-primary" onclick="window.print();">
                        📸 Simpan / Cetak Tiket
                    </button>
                </div>
            </div>
        </div>

        {{-- Footer Hak Cipta --}}
        <p class="ticket-footer">
            &copy; {{ date('Y') }} {{ $settings->app_name ?: 'Guest Management' }}. Tiket Digital Resmi.
        </p>
    </div>
</body>
</html>
