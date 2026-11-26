@php
    $viewData = $this->getViewData();
    $activeEvents = $viewData['activeEvents'];
    $currentEvent = $viewData['currentEvent'];
    $tables = $viewData['tables'];
    $mappedTables = $viewData['mappedTables'];
    $unmappedTables = $viewData['unmappedTables'];
    $activeTable = $viewData['activeTable'];
    $unassignedGuests = $viewData['unassignedGuests'];
    $tenants = $viewData['tenants'];
    $zones = $viewData['zones'];
    $settings = $viewData['settings'];
@endphp

<x-filament-panels::page>
    <style>
        :root {
            --brand-primary: {{ $settings->primary_color ?? '#2f76b5' }};
            --brand-secondary: {{ $settings->secondary_color ?? '#1e4e79' }};
        }

        .tm-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .dark .tm-card {
            background-color: #1e293b;
            border-color: #334155;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.2);
        }

        .tm-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
        }

        .tm-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: 0.625rem;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            user-select: none;
        }
        .tm-btn:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
        }
        .dark .tm-btn {
            background-color: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }
        .dark .tm-btn:hover {
            background-color: #1e293b;
            border-color: #475569;
        }

        .tm-btn-primary {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary)) !important;
            border-color: transparent !important;
            color: #ffffff !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .tm-btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .tm-btn-active {
            background-color: #eff6ff !important;
            border-color: var(--brand-primary) !important;
            color: var(--brand-primary) !important;
            font-weight: 700;
        }
        .dark .tm-btn-active {
            background-color: rgba(47, 118, 181, 0.2) !important;
            border-color: var(--brand-primary) !important;
            color: #60a5fa !important;
        }

        .tm-select {
            max-width: 100%;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: 0.625rem;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #1e293b;
            outline: none;
            transition: border-color 0.15s ease-in-out;
        }
        .tm-select:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 2px rgba(47, 118, 181, 0.15);
        }
        .dark .tm-select {
            background-color: #0f172a;
            border-color: #334155;
            color: #f1f5f9;
        }

        /* Viewport & Real-Dimension Zoom Canvas */
        .tm-viewport {
            position: relative;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            background-color: #0f172a;
            min-height: 420px;
            max-height: 75vh;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        @media (min-width: 768px) {
            .tm-viewport {
                min-height: 540px;
            }
        }
        .dark .tm-viewport {
            border-color: #1e293b;
            background-color: #050811;
        }
        .tm-viewport.is-pannable {
            cursor: grab;
        }
        .tm-viewport.is-panning {
            cursor: grabbing !important;
            user-select: none;
        }

        .tm-zoom-container {
            position: relative;
            display: block;
            flex-shrink: 0;
            transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Skala Zoom Berbasis Dimensi Nyata (Bukan CSS transform: scale) */
        @media (min-width: 768px) {
            .tm-zoom-container[data-zoom="numeric"] {
                width: calc(100% * var(--tm-zoom-factor, 1));
                min-width: calc(100% * var(--tm-zoom-factor, 1));
            }
            .tm-zoom-container[data-zoom="fit"] {
                width: 100%;
            }
            .tm-fullscreen-wrapper.is-fullscreen .tm-viewport.is-fit-desktop,
            .tm-fullscreen-wrapper:fullscreen .tm-viewport.is-fit-desktop {
                justify-content: center !important;
                align-items: center !important;
                overflow: hidden !important;
            }
            .tm-fullscreen-wrapper.is-fullscreen .tm-zoom-container[data-zoom="fit"],
            .tm-fullscreen-wrapper:fullscreen .tm-zoom-container[data-zoom="fit"] {
                width: auto !important;
                display: inline-block !important;
            }
            .tm-fullscreen-wrapper.is-fullscreen .tm-zoom-container[data-zoom="fit"] .tm-inner-canvas,
            .tm-fullscreen-wrapper:fullscreen .tm-zoom-container[data-zoom="fit"] .tm-inner-canvas {
                width: auto !important;
                display: inline-block !important;
            }
            .tm-fullscreen-wrapper.is-fullscreen .tm-zoom-container[data-zoom="fit"] .tm-floorplan-img,
            .tm-fullscreen-wrapper:fullscreen .tm-zoom-container[data-zoom="fit"] .tm-floorplan-img {
                width: auto !important;
                height: auto !important;
                max-height: calc(100vh - 110px) !important;
                max-width: 100% !important;
            }
        }

        @media (max-width: 767px) {
            .tm-zoom-container[data-zoom="numeric"] {
                width: calc(820px * var(--tm-zoom-factor, 1));
                min-width: calc(820px * var(--tm-zoom-factor, 1));
            }
            .tm-zoom-container[data-zoom="fit"] {
                width: 100%;
                min-width: 0;
            }
        }

        .tm-inner-canvas {
            position: relative;
            display: inline-block;
            width: 100%;
            line-height: 0;
            user-select: none;
            cursor: default;
        }
        .tm-inner-canvas.is-placing {
            cursor: crosshair !important;
        }
        .tm-floorplan-img {
            display: block;
            width: 100%;
            height: auto;
            max-width: none;
            pointer-events: none;
            user-select: none;
        }

        /* Table Pins */
        .tm-pin {
            position: absolute;
            transform: translate(-50%, -50%);
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.75rem;
            line-height: 1;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35), 0 0 0 2.5px #ffffff;
            cursor: pointer;
            touch-action: manipulation;
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.2s ease;
            z-index: 10;
        }
        @media (max-width: 767px) {
            .tm-pin.tm-pin-compact {
                width: 30px;
                min-width: 30px;
                height: 30px;
                font-size: 11px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35), 0 0 0 2px #ffffff;
            }
            .tm-pin.tm-pin-compact .tm-pin-capacity {
                display: none;
            }
        }
        .dark .tm-pin {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5), 0 0 0 2.5px #0f172a;
        }
        .tm-pin:hover {
            transform: translate(-50%, -50%) scale(1.18);
            z-index: 30;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.45), 0 0 0 3px #ffffff;
        }
        .tm-pin.is-selected {
            transform: translate(-50%, -50%) scale(1.22);
            z-index: 35;
            box-shadow: 0 0 0 4px #3b82f6, 0 8px 20px rgba(59, 130, 246, 0.5);
            animation: tmPinPulse 1.8s infinite;
        }
        .tm-pin.is-highlighted {
            box-shadow: 0 0 0 4px #fbbf24, 0 8px 20px rgba(251, 191, 36, 0.6) !important;
            animation: tmPinPulseGold 1.5s infinite;
            z-index: 25;
        }
        .tm-pin.is-dimmed {
            opacity: 0.28 !important;
            filter: grayscale(40%);
        }

        @keyframes tmPinPulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        @keyframes tmPinPulseGold {
            0% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.8); }
            70% { box-shadow: 0 0 0 12px rgba(251, 191, 36, 0); }
            100% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0); }
        }

        .tm-pin-capacity {
            font-size: 0.5625rem;
            font-weight: 700;
            opacity: 0.92;
            margin-top: 1px;
        }

        /* Tooltip */
        .tm-tooltip {
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(8px);
            color: #ffffff;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.6875rem;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: all 0.15s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
            z-index: 50;
        }
        .tm-pin:hover .tm-tooltip {
            opacity: 1;
            visibility: visible;
        }

        /* Unmapped Tray */
        .tm-tray {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding: 0.75rem 1rem;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 1rem 1rem;
        }
        .dark .tm-tray {
            background-color: #0f172a;
            border-color: #334155;
        }

        .tm-tray-item {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 90px;
            padding: 0.5rem 0.75rem;
            border-radius: 0.625rem;
            border: 1.5px dashed #94a3b8;
            background-color: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
        }
        .dark .tm-tray-item {
            background-color: #1e293b;
            border-color: #475569;
        }
        .tm-tray-item:hover {
            border-color: var(--brand-primary);
            background-color: #eff6ff;
            transform: translateY(-2px);
        }
        .dark .tm-tray-item:hover {
            background-color: #334155;
            border-color: #60a5fa;
        }
        .tm-tray-item.is-selected {
            border-style: solid;
            border-color: var(--brand-primary);
            background-color: #eff6ff;
            box-shadow: 0 0 0 2px var(--brand-primary);
        }
        .dark .tm-tray-item.is-selected {
            background-color: rgba(47, 118, 181, 0.3);
            border-color: #60a5fa;
        }
        .tm-tray-name {
            font-size: 0.75rem;
            font-weight: 800;
            color: #0f172a;
        }
        .dark .tm-tray-name {
            color: #f8fafc;
        }
        .tm-tray-meta {
            font-size: 0.625rem;
            color: #64748b;
        }
        .dark .tm-tray-meta {
            color: #94a3b8;
        }
        .tm-tray-zone {
            font-size: 0.5625rem;
            padding: 0.1rem 0.375rem;
            border-radius: 0.25rem;
            background-color: #e2e8f0;
            color: #334155;
            font-weight: 600;
            margin-top: 0.15rem;
        }
        .dark .tm-tray-zone {
            background-color: #334155;
            color: #e2e8f0;
        }

        /* Slide-over Drawer */
        .tm-drawer-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 10050;
            display: flex;
            justify-content: flex-end;
            transition: opacity 0.25s ease;
        }
        .tm-drawer-panel {
            width: 100%;
            max-width: 480px;
            height: 100%;
            background-color: #ffffff;
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: tmSlideIn 0.25s ease-out;
        }
        .dark .tm-drawer-panel {
            background-color: #1e293b;
            color: #f1f5f9;
        }
        @keyframes tmSlideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        .tm-drawer-header {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .dark .tm-drawer-header {
            background-color: #0f172a;
            border-bottom-color: #334155;
        }
        .tm-drawer-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }
        .dark .tm-drawer-title {
            color: #f8fafc;
        }
        .tm-drawer-subtitle {
            font-size: 0.75rem;
            color: #64748b;
            margin: 0.15rem 0 0;
        }
        .dark .tm-drawer-subtitle {
            color: #94a3b8;
        }
        .tm-drawer-zone-badge {
            font-size: 0.625rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            background-color: #e2e8f0;
            color: #334155;
        }
        .dark .tm-drawer-zone-badge {
            background-color: #334155;
            color: #e2e8f0;
        }
        .tm-drawer-close {
            padding: 0.375rem;
            border-radius: 0.625rem;
            color: #64748b;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .tm-drawer-close:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }
        .dark .tm-drawer-close {
            color: #94a3b8;
        }
        .dark .tm-drawer-close:hover {
            background-color: #334155;
            color: #f8fafc;
        }
        .tm-drawer-capacity-box {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .dark .tm-drawer-capacity-box {
            border-bottom-color: #334155;
        }
        .tm-drawer-progress-track {
            width: 100%;
            height: 0.625rem;
            border-radius: 9999px;
            background-color: #e2e8f0;
            overflow: hidden;
        }
        .dark .tm-drawer-progress-track {
            background-color: #334155;
        }
        .tm-drawer-assign-box {
            padding: 0.875rem;
            border-radius: 0.75rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .dark .tm-drawer-assign-box {
            background-color: #0f172a;
            border-color: #334155;
        }
        .tm-drawer-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #334155;
            margin: 0;
        }
        .dark .tm-drawer-section-title {
            color: #cbd5e1;
        }
        .tm-drawer-panel .tm-select option {
            background-color: #ffffff;
            color: #0f172a;
        }
        .dark .tm-drawer-panel .tm-select option {
            background-color: #0f172a;
            color: #f1f5f9;
        }
        .tm-guest-card {
            padding: 0.75rem 0.875rem;
            border-radius: 0.75rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.625rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        .dark .tm-guest-card {
            background-color: rgba(15, 23, 42, 0.75);
            border-color: #334155;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        }
        .tm-guest-name {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
        }
        .dark .tm-guest-name {
            color: #f8fafc;
        }
        .tm-guest-pax {
            font-size: 0.625rem;
            font-weight: 700;
            padding: 0.125rem 0.4rem;
            border-radius: 0.375rem;
            background-color: #e2e8f0;
            color: #334155;
            white-space: nowrap;
        }
        .dark .tm-guest-pax {
            background-color: #334155;
            color: #e2e8f0;
        }
        .tm-guest-meta {
            font-size: 0.7rem;
            color: #475569;
            margin-top: 0.2rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.375rem;
        }
        .dark .tm-guest-meta {
            color: #94a3b8;
        }
        .tm-status-attended {
            color: #059669;
            font-weight: 700;
        }
        .dark .tm-status-attended {
            color: #34d399;
        }
        .tm-status-pending {
            color: #64748b;
            font-weight: 600;
        }
        .dark .tm-status-pending {
            color: #94a3b8;
        }
        .tm-unassign-btn {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.35rem 0.65rem;
            border-radius: 0.5rem;
            color: #dc2626;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            cursor: pointer;
            flex-shrink: 0;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }
        .tm-unassign-btn:hover {
            background-color: #fee2e2;
            border-color: #fca5a5;
        }
        .dark .tm-unassign-btn {
            color: #fca5a5;
            background-color: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
        }
        .dark .tm-unassign-btn:hover {
            background-color: rgba(239, 68, 68, 0.28);
        }
        .tm-drawer-empty {
            padding: 1.5rem;
            text-align: center;
            font-size: 0.75rem;
            color: #64748b;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 0.75rem;
        }
        .dark .tm-drawer-empty {
            color: #94a3b8;
            background-color: rgba(15, 23, 42, 0.45);
            border-color: #334155;
        }

        /* Fullscreen Mode & Floating Toolbar */
        .tm-fullscreen-wrapper {
            position: relative;
        }
        .tm-fullscreen-wrapper.is-fullscreen,
        .tm-fullscreen-wrapper:fullscreen {
            position: fixed !important;
            inset: 0 !important;
            z-index: 9999 !important;
            width: 100vw !important;
            height: 100vh !important;
            background-color: #0b0f19 !important;
            padding: 0.75rem !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
            overflow: hidden !important;
        }
        .tm-fullscreen-wrapper.is-fullscreen .tm-canvas-card,
        .tm-fullscreen-wrapper:fullscreen .tm-canvas-card {
            flex: 1 1 auto !important;
            display: flex !important;
            flex-direction: column !important;
            min-height: 0 !important;
            border-radius: 0.75rem !important;
        }
        .tm-fullscreen-wrapper.is-fullscreen .tm-viewport,
        .tm-fullscreen-wrapper:fullscreen .tm-viewport {
            flex: 1 1 auto !important;
            max-height: none !important;
            height: 100% !important;
            border-radius: 0.75rem !important;
        }
        .tm-fullscreen-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.625rem 1rem;
            border-radius: 0.875rem;
            background: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.25);
            color: #f8fafc;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
            flex-shrink: 0;
        }
        .tm-fs-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: #ffffff;
            font-size: 0.8125rem;
            font-weight: 700;
        }
        .tm-fs-select {
            padding: 0.375rem 1.75rem 0.375rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: 1px solid #334155;
            background-color: #1e293b;
            color: #f1f5f9;
            outline: none;
        }
        .tm-fs-exit-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.4375rem 0.875rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 0.5rem;
            background-color: #dc2626;
            color: #ffffff;
            border: 1px solid #ef4444;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .tm-fs-exit-btn:hover {
            background-color: #b91c1c;
        }

        /* Quick Table Switcher Bar (Daftar Cepat Meja) */
        .tm-quick-switcher {
            padding: 0.875rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }
        .tm-qs-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .tm-qs-zones {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.375rem;
        }
        .tm-zone-pill {
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .dark .tm-zone-pill {
            background-color: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }
        .tm-zone-pill.is-active {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            border-color: transparent;
            color: #ffffff;
        }
        .tm-pills-scroll {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.25rem;
        }
        .tm-table-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #1e293b;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
            cursor: pointer;
            touch-action: manipulation;
            transition: all 0.15s ease;
        }
        .dark .tm-table-pill {
            background-color: #0f172a;
            border-color: #334155;
            color: #f1f5f9;
        }
        .tm-table-pill:hover,
        .tm-table-pill.is-active {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 2px rgba(47, 118, 181, 0.2);
        }
        .tm-status-dot {
            width: 9px;
            height: 9px;
            border-radius: 9999px;
            flex-shrink: 0;
        }
    </style>

    <div x-data="{
        mode: @entangle('mode'),
        zoomLevel: @entangle('zoomLevel'),
        selectedTableForPosition: @entangle('selectedTableForPosition'),
        isFullscreen: false,
        quickZoneFilter: 'all',
        dragTargetId: null,
        startX: 0,
        startY: 0,
        isDragging: false,
        hasMovedPastThreshold: false,
        isPanPending: false,
        isPanning: false,
        justPanned: false,
        panStartX: 0,
        panStartY: 0,
        panStartScrollLeft: 0,
        panStartScrollTop: 0,

        init() {
            document.addEventListener('fullscreenchange', () => {
                if (!document.fullscreenElement && this.isFullscreen) {
                    this.isFullscreen = false;
                    if (this.zoomLevel === 'fit') {
                        $wire.setZoom('100');
                    }
                }
            });
        },

        async toggleFullscreen() {
            if (this.isFullscreen) {
                await this.exitFullscreen();
                return;
            }
            this.isFullscreen = true;
            $wire.setZoom('fit');
            const el = this.$refs.fullscreenContainer;
            if (el && el.requestFullscreen) {
                try {
                    await el.requestFullscreen();
                } catch (e) {
                    // Fallback CSS fixed overlay tetap aktif saat native Fullscreen API ditolak
                }
            }
        },

        async exitFullscreen() {
            this.isFullscreen = false;
            if (this.zoomLevel === 'fit') {
                $wire.setZoom('100');
            }
            if (document.fullscreenElement && document.exitFullscreen) {
                try {
                    await document.exitFullscreen();
                } catch (e) {
                    // Abaikan jika sudah keluar dari native fullscreen
                }
            }
        },

        getZoomFactor() {
            if (this.zoomLevel === 'fit') return 1;
            const parsed = parseInt(this.zoomLevel, 10);
            return Number.isNaN(parsed) ? 1 : (parsed / 100);
        },

        startPan(event) {
            if (event.button !== 0) return;
            if (this.dragTargetId) return;
            const viewport = this.$refs.viewport;
            if (!viewport) return;
            this.isPanPending = true;
            this.isPanning = false;
            this.panStartX = event.clientX;
            this.panStartY = event.clientY;
            this.panStartScrollLeft = viewport.scrollLeft;
            this.panStartScrollTop = viewport.scrollTop;
        },

        startDrag(tableId, event) {
            if (this.mode !== 'position') return;
            this.dragTargetId = tableId;
            this.startX = event.clientX || (event.touches ? event.touches[0].clientX : 0);
            this.startY = event.clientY || (event.touches ? event.touches[0].clientY : 0);
            this.hasMovedPastThreshold = false;
            this.isDragging = false;
        },

        onWindowMouseMove(event) {
            if (this.dragTargetId) {
                const clientX = event.clientX || (event.touches ? event.touches[0].clientX : 0);
                const clientY = event.clientY || (event.touches ? event.touches[0].clientY : 0);
                const distance = Math.hypot(clientX - this.startX, clientY - this.startY);
                if (distance > 4) {
                    this.hasMovedPastThreshold = true;
                    this.isDragging = true;
                }
                return;
            }

            if (this.isPanPending) {
                const dx = event.clientX - this.panStartX;
                const dy = event.clientY - this.panStartY;
                if (Math.hypot(dx, dy) > 4) {
                    this.isPanning = true;
                    const viewport = this.$refs.viewport;
                    if (viewport) {
                        viewport.scrollLeft = this.panStartScrollLeft - dx;
                        viewport.scrollTop = this.panStartScrollTop - dy;
                    }
                }
            }
        },

        onWindowMouseUp(event) {
            if (this.isDragging && this.dragTargetId) {
                const clientX = event.clientX || (event.changedTouches ? event.changedTouches[0].clientX : 0);
                const clientY = event.clientY || (event.changedTouches ? event.changedTouches[0].clientY : 0);
                const rect = this.$refs.canvas.getBoundingClientRect();
                const dropX = clientX - rect.left;
                const dropY = clientY - rect.top;

                if (dropX >= 0 && dropX <= rect.width && dropY >= 0 && dropY <= rect.height) {
                    const posX = parseFloat(((dropX / rect.width) * 100).toFixed(2));
                    const posY = parseFloat(((dropY / rect.height) * 100).toFixed(2));
                    $wire.saveTablePosition(this.dragTargetId, posX, posY);
                }
            }

            if (this.isPanning) {
                this.justPanned = true;
                setTimeout(() => {
                    this.justPanned = false;
                }, 60);
            }

            this.isPanPending = false;
            this.isPanning = false;
            this.dragTargetId = null;
            setTimeout(() => {
                this.isDragging = false;
                this.hasMovedPastThreshold = false;
            }, 60);
        },

        handlePinClick(tableId) {
            if (this.isDragging || this.hasMovedPastThreshold || this.justPanned) return;

            if (this.mode === 'plotting') {
                $wire.openTableDrawer(tableId);
            } else {
                this.selectedTableForPosition = tableId;
                $wire.selectTableForPosition(tableId);
            }
        },

        // Detail 2: Presisi Klik Saat Zoom dihitung langsung dari inner canvas wrapper ($refs.canvas)
        handleCanvasClick(event) {
            if (this.isDragging || this.hasMovedPastThreshold || this.justPanned) return;
            if (this.mode !== 'position') return;
            if (!this.selectedTableForPosition) return;

            const rect = this.$refs.canvas.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const clickY = event.clientY - rect.top;

            if (clickX >= 0 && clickX <= rect.width && clickY >= 0 && clickY <= rect.height) {
                const posX = parseFloat(((clickX / rect.width) * 100).toFixed(2));
                const posY = parseFloat(((clickY / rect.height) * 100).toFixed(2));
                $wire.saveTablePosition(this.selectedTableForPosition, posX, posY);
            }
        },

        scrollToPin(posX, posY) {
            const viewport = this.$refs.viewport;
            const canvas = this.$refs.canvas;
            if (!viewport || !canvas) return;
            const targetX = (canvas.offsetWidth * (posX / 100)) - (viewport.clientWidth / 2);
            const targetY = (canvas.offsetHeight * (posY / 100)) - (viewport.clientHeight / 2);
            viewport.scrollTo({
                left: Math.max(0, targetX),
                top: Math.max(0, targetY),
                behavior: 'smooth',
            });
        },

        handleQuickTableClick(tableId, posX, posY) {
            this.scrollToPin(posX, posY);
            if (this.mode === 'plotting') {
                $wire.openTableDrawer(tableId);
            } else {
                this.selectedTableForPosition = tableId;
                $wire.selectTableForPosition(tableId);
            }
        }
    }"
    @mousemove.window="onWindowMouseMove($event)"
    @mouseup.window="onWindowMouseUp($event)"
    @touchmove.window="onWindowMouseMove($event)"
    @touchend.window="onWindowMouseUp($event)"
    @keydown.escape.window="if (isFullscreen) exitFullscreen()"
    class="space-y-4">

        {{-- TOP CONTROL TOOLBAR --}}
        <div class="tm-card tm-toolbar">
            <div class="flex items-center flex-wrap gap-2.5">
                {{-- Event Selector --}}
                <div class="flex items-center flex-wrap gap-2 max-w-full">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Acara:</span>
                    <select wire:model.live="selectedEventId" class="tm-select">
                        @forelse($activeEvents as $event)
                            <option value="{{ $event->id }}">{{ $event->name }}</option>
                        @empty
                            <option value="">Tidak ada acara aktif</option>
                        @endforelse
                    </select>
                </div>

                {{-- Mode Switcher --}}
                <div class="inline-flex flex-wrap rounded-xl bg-slate-100 dark:bg-slate-900 p-1 border border-slate-200 dark:border-slate-800 max-w-full">
                    <button type="button"
                            wire:click="setMode('plotting')"
                            class="tm-btn !border-0 {{ $mode === 'plotting' ? 'tm-btn-primary' : '' }}"
                            title="Mode Plotting Tamu & Monitoring Kapasitas">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Plotting & Monitoring</span>
                    </button>
                    <button type="button"
                            wire:click="setMode('position')"
                            class="tm-btn !border-0 {{ $mode === 'position' ? 'tm-btn-primary' : '' }}"
                            title="Mode Atur Posisi Koordinat Pin Meja">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Atur Posisi Pin</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center flex-wrap gap-2.5">
                {{-- Filter Kapasitas --}}
                <select wire:model.live="statusFilter" class="tm-select text-xs">
                    <option value="all">Semua Meja</option>
                    <option value="available">Tersedia Kursi (>0)</option>
                    <option value="full">Meja Penuh (0)</option>
                </select>

                {{-- Filter Tenant --}}
                @if($tenants->count() > 0)
                    <select wire:model.live="tenantFilter" class="tm-select text-xs">
                        <option value="all">Semua Tenant</option>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                        @endforeach
                    </select>
                @endif

                {{-- Zoom Controls --}}
                <div class="inline-flex flex-wrap items-center rounded-xl bg-slate-100 dark:bg-slate-900 p-1 border border-slate-200 dark:border-slate-800 text-xs">
                    <button type="button" wire:click="setZoom('fit')" class="tm-btn !border-0 !py-1 !px-2.5 {{ $zoomLevel === 'fit' ? 'tm-btn-active' : '' }}" title="Sesuaikan Seluruh Denah ke Layar">Fit Layar</button>
                    <button type="button" wire:click="setZoom('100')" class="tm-btn !border-0 !py-1 !px-2.5 {{ $zoomLevel === '100' ? 'tm-btn-active' : '' }}">100%</button>
                    <button type="button" wire:click="setZoom('125')" class="tm-btn !border-0 !py-1 !px-2.5 {{ $zoomLevel === '125' ? 'tm-btn-active' : '' }}">125%</button>
                    <button type="button" wire:click="setZoom('150')" class="tm-btn !border-0 !py-1 !px-2.5 {{ $zoomLevel === '150' ? 'tm-btn-active' : '' }}">150%</button>
                    <button type="button" wire:click="setZoom('200')" class="tm-btn !border-0 !py-1 !px-2.5 {{ $zoomLevel === '200' ? 'tm-btn-active' : '' }}">200%</button>
                </div>

                {{-- Tombol Fullscreen Denah --}}
                <button type="button"
                        @click="toggleFullscreen()"
                        class="tm-btn tm-btn-primary !py-1.5 !px-3 text-xs"
                        title="Tampilkan Denah Layar Penuh">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                    </svg>
                    <span>Fullscreen Denah</span>
                </button>

                {{-- Indikator Statistik --}}
                <div class="text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    <span class="text-emerald-600 dark:text-emerald-400">{{ $mappedTables->count() }}</span> / {{ $tables->count() }} Meja Terpetakan
                </div>
            </div>
        </div>

        {{-- NOTIFIKASI PETUNJUK MODE ATUR POSISI --}}
        @if($mode === 'position')
            <div class="p-3.5 rounded-xl bg-sky-50 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-800 flex items-center justify-between text-xs text-sky-800 dark:text-sky-300">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-sky-500 animate-ping"></span>
                    <span>
                        @if($selectedTableForPosition)
                            @php
                                $selectedTableObj = $tables->firstWhere('id', $selectedTableForPosition);
                            @endphp
                            <strong>Mode Penempatan Aktif:</strong> Klik di mana saja pada denah untuk meletakkan <strong>{{ $selectedTableObj->name ?? 'Meja Terpilih' }}</strong>, atau seret pin yang ada untuk menggeser posisinya.
                        @else
                            <strong>Mode Atur Posisi Aktif:</strong> Pilih salah satu meja dari daftar di bawah (atau klik pin yang ada di denah), lalu klik titik pada denah untuk menetapkan koordinatnya.
                        @endif
                    </span>
                </div>
                @if($selectedTableForPosition)
                    <button type="button" wire:click="selectTableForPosition(null)" class="text-xs underline font-bold hover:text-sky-950 dark:hover:text-sky-100">
                        Batal Pilih
                    </button>
                @endif
            </div>
        @endif

        {{-- FULLSCREEN CONTAINER (CANVAS + FLOATING TOOLBAR + DRAWER) --}}
        <div x-ref="fullscreenContainer"
             class="tm-fullscreen-wrapper"
             :class="{ 'is-fullscreen': isFullscreen }">

            {{-- Floating Toolbar saat Fullscreen Aktif --}}
            <div x-show="isFullscreen" x-cloak class="tm-fullscreen-toolbar">
                <div class="flex items-center flex-wrap gap-2.5">
                    <span class="tm-fs-badge">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <span>{{ $currentEvent->name ?? 'Denah Acara' }}</span>
                    </span>

                    <select wire:model.live="statusFilter" class="tm-fs-select">
                        <option value="all">Semua Meja</option>
                        <option value="available">Tersedia Kursi (>0)</option>
                        <option value="full">Meja Penuh (0)</option>
                    </select>

                    @if($tenants->count() > 0)
                        <select wire:model.live="tenantFilter" class="tm-fs-select">
                            <option value="all">Semua Tenant</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="flex items-center flex-wrap gap-2">
                    <div class="inline-flex flex-wrap items-center rounded-lg bg-slate-800 p-0.5 border border-slate-700 text-xs">
                        <button type="button" wire:click="setZoom('fit')" class="tm-btn !border-0 !py-1 !px-2 text-xs {{ $zoomLevel === 'fit' ? 'tm-btn-primary' : '!bg-transparent !text-slate-300' }}">Fit</button>
                        <button type="button" wire:click="setZoom('100')" class="tm-btn !border-0 !py-1 !px-2 text-xs {{ $zoomLevel === '100' ? 'tm-btn-primary' : '!bg-transparent !text-slate-300' }}">100%</button>
                        <button type="button" wire:click="setZoom('125')" class="tm-btn !border-0 !py-1 !px-2 text-xs {{ $zoomLevel === '125' ? 'tm-btn-primary' : '!bg-transparent !text-slate-300' }}">125%</button>
                        <button type="button" wire:click="setZoom('150')" class="tm-btn !border-0 !py-1 !px-2 text-xs {{ $zoomLevel === '150' ? 'tm-btn-primary' : '!bg-transparent !text-slate-300' }}">150%</button>
                        <button type="button" wire:click="setZoom('200')" class="tm-btn !border-0 !py-1 !px-2 text-xs {{ $zoomLevel === '200' ? 'tm-btn-primary' : '!bg-transparent !text-slate-300' }}">200%</button>
                        <button type="button" wire:click="setZoom('fit')" class="tm-btn !border-0 !py-1 !px-2 text-xs !bg-transparent !text-slate-300 hover:!text-white">Reset</button>
                    </div>

                    <button type="button" @click="exitFullscreen()" class="tm-fs-exit-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                        </svg>
                        <span>Keluar Fullscreen (ESC)</span>
                    </button>
                </div>
            </div>

            {{-- CANVAS VIEWPORT AREA --}}
            <div class="tm-card tm-canvas-card overflow-hidden">
            @if(! $currentEvent || ! $currentEvent->getFloorPlanUrl())
                {{-- Empty State: Denah Belum Diunggah --}}
                <div class="p-12 text-center flex flex-col items-center justify-center min-h-[440px] space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-amber-100 dark:bg-amber-950/80 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="space-y-1 max-w-md">
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Denah Ruangan Belum Diunggah</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Acara <strong>{{ $currentEvent->name ?? 'Terpilih' }}</strong> belum memiliki gambar denah venue. Unggah gambar denah (PNG, JPG, WebP) pada menu Master Acara untuk mulai memetakan meja.
                        </p>
                    </div>
                    @if($currentEvent)
                        <a href="{{ \App\Filament\Resources\EventResource::getUrl('edit', ['record' => $currentEvent->id]) }}"
                           class="tm-btn tm-btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>Unggah Denah di Acara</span>
                        </a>
                    @endif
                </div>
            @else
                {{-- Viewport dengan Kontrol Zoom Dimensi Nyata & Mouse Drag-to-Pan --}}
                <div class="tm-viewport"
                     x-ref="viewport"
                     :class="{
                         'is-fit-desktop': isFullscreen && zoomLevel === 'fit',
                         'is-pannable': !isPanning && (zoomLevel !== 'fit' || !isFullscreen),
                         'is-panning': isPanning
                     }"
                     @mousedown="startPan($event)">
                    <div class="tm-zoom-container"
                         data-zoom="{{ $zoomLevel === 'fit' ? 'fit' : 'numeric' }}"
                         :style="'--tm-zoom-factor: ' + getZoomFactor() + ';'">
                        {{-- Detail 2: Inner Canvas Wrapper tempat @click listener dipasang --}}
                        <div class="tm-inner-canvas"
                             :class="{ 'is-placing': mode === 'position' && selectedTableForPosition }"
                             x-ref="canvas"
                             @click="handleCanvasClick($event)">
                            {{-- Gambar Denah Ruangan --}}
                            <img src="{{ $currentEvent->getFloorPlanUrl() }}"
                                 alt="Denah {{ $currentEvent->name }}"
                                 class="tm-floorplan-img" />

                            {{-- Render Pin Meja Terpetakan --}}
                            @foreach($mappedTables as $table)
                                @php
                                    $remaining = $table->getRemainingSeats();
                                    $occupied = $table->getOccupiedSeats();
                                    $statusColor = $table->getStatusColor();

                                    // Filter Status
                                    $isDimmedByStatus = false;
                                    if ($statusFilter === 'available' && $remaining <= 0) {
                                        $isDimmedByStatus = true;
                                    } elseif ($statusFilter === 'full' && $remaining > 0) {
                                        $isDimmedByStatus = true;
                                    }

                                    // Filter Tenant: Cek apakah ada tamu dari tenant ini di meja tersebut
                                    $isHighlightedByTenant = false;
                                    $isDimmedByTenant = false;
                                    if ($tenantFilter !== 'all') {
                                        $hasTenantGuest = $table->guests->contains(fn ($g) => (string) $g->tenant_id === (string) $tenantFilter);
                                        if ($hasTenantGuest) {
                                            $isHighlightedByTenant = true;
                                        } else {
                                            $isDimmedByTenant = true;
                                        }
                                    }

                                    $isDimmed = $isDimmedByStatus || $isDimmedByTenant;
                                @endphp

                                {{-- Detail 3: wire:key pada pin, @click.stop, drag threshold > 4px --}}
                                <div wire:key="pin-{{ $table->id }}"
                                     class="tm-pin {{ $zoomLevel === 'fit' ? 'tm-pin-compact' : '' }} {{ $isDimmed ? 'is-dimmed' : '' }} {{ $isHighlightedByTenant ? 'is-highlighted' : '' }}"
                                     :class="{ 'is-selected': selectedTableForPosition == {{ $table->id }} || {{ $activeTableId == $table->id ? 'true' : 'false' }} }"
                                     style="left: {{ $table->pos_x }}%; top: {{ $table->pos_y }}%; background-color: {{ $statusColor }};"
                                     @mousedown.stop="startDrag({{ $table->id }}, $event)"
                                     @touchstart.stop="startDrag({{ $table->id }}, $event)"
                                     @click.stop="handlePinClick({{ $table->id }})">
                                    
                                    {{-- Nomor / Label Meja --}}
                                    <span class="truncate max-w-[36px]">{{ $table->name }}</span>
                                    <span class="tm-pin-capacity">{{ $occupied }}/{{ $table->capacity }}</span>

                                    {{-- Tooltip Hover Instan --}}
                                    <div class="tm-tooltip">
                                        <div class="font-extrabold text-white text-xs">{{ $table->name }}</div>
                                        @if($table->zone)
                                            <div class="text-[10px] text-slate-300">Zona: {{ $table->zone }}</div>
                                        @endif
                                        @if($table->pic_name)
                                            <div class="text-[10px] text-slate-300">PIC: {{ $table->pic_name }}</div>
                                        @endif
                                        <div class="text-[10px] font-semibold text-emerald-300 mt-0.5">
                                            Terisi: {{ $occupied }} / {{ $table->capacity }} (Sisa: {{ $remaining }})
                                        </div>
                                        @if($mode === 'position')
                                            <div class="mt-1 pt-1 border-t border-slate-700 flex items-center justify-between gap-2 pointer-events-auto">
                                                <button type="button"
                                                        wire:click.stop="resetTablePosition({{ $table->id }})"
                                                        class="text-[9px] text-rose-300 hover:text-rose-100 font-bold underline">
                                                    Angkat Pin
                                                </button>
                                                <span class="text-[9px] text-slate-400">Tahan & Seret</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- BAKI MEJA BELUM DIPETAKAN (MODE ATUR POSISI) --}}
                @if($mode === 'position')
                    <div class="tm-tray">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-500 uppercase tracking-wider shrink-0 pr-2 border-r border-slate-200 dark:border-slate-700">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Belum Dipetakan ({{ $unmappedTables->count() }}):</span>
                        </div>

                        @forelse($unmappedTables as $unmappedTable)
                            <div wire:key="unmapped-{{ $unmappedTable->id }}"
                                 class="tm-tray-item"
                                 :class="{ 'is-selected': selectedTableForPosition == {{ $unmappedTable->id }} }"
                                 wire:click="selectTableForPosition({{ $unmappedTable->id }})">
                                <span class="tm-tray-name">{{ $unmappedTable->name }}</span>
                                <span class="tm-tray-meta">{{ $unmappedTable->capacity }} Kursi</span>
                                @if($unmappedTable->zone)
                                    <span class="tm-tray-zone">
                                        {{ $unmappedTable->zone }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <div class="text-xs text-slate-400 italic py-2">
                                Semua meja pada acara ini sudah berhasil dipetakan pada denah.
                            </div>
                        @endforelse
                    </div>
                @endif
            @endif
        </div>

        {{-- QUICK TABLE SWITCHER BAR (DAFTAR CEPAT MEJA) --}}
        @if($mappedTables->count() > 0)
            <div x-show="!isFullscreen" class="tm-card tm-quick-switcher">
                <div class="tm-qs-header">
                    <span class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                        Daftar Cepat Meja ({{ $mappedTables->count() }})
                    </span>

                    <div class="tm-qs-zones">
                        <button type="button"
                                @click="quickZoneFilter = 'all'"
                                class="tm-zone-pill"
                                :class="{ 'is-active': quickZoneFilter === 'all' }">
                            Semua Zona
                        </button>
                        @foreach($zones as $zoneName)
                            <button type="button"
                                    @click="quickZoneFilter = '{{ addslashes($zoneName) }}'"
                                    class="tm-zone-pill"
                                    :class="{ 'is-active': quickZoneFilter === '{{ addslashes($zoneName) }}' }">
                                {{ $zoneName }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="tm-pills-scroll">
                    @foreach($mappedTables as $qt)
                        @php
                            $qtOccupied = $qt->getOccupiedSeats();
                            $qtColor = $qt->getStatusColor();
                            $qtZone = $qt->zone ?? '';
                        @endphp
                        <button type="button"
                                wire:key="quick-pill-{{ $qt->id }}"
                                x-show="quickZoneFilter === 'all' || quickZoneFilter === '{{ addslashes($qtZone) }}'"
                                @click="handleQuickTableClick({{ $qt->id }}, {{ $qt->pos_x }}, {{ $qt->pos_y }})"
                                class="tm-table-pill {{ $activeTableId == $qt->id ? 'is-active' : '' }}">
                            <span class="tm-status-dot" style="background-color: {{ $qtColor }};"></span>
                            <span>{{ $qt->name }}</span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold">
                                {{ $qtOccupied }}/{{ $qt->capacity }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- SLIDE-OVER DRAWER (MODE PLOTTING & MONITORING) --}}
        @if($activeTable)
            @php
                $tableOccupied = $activeTable->getOccupiedSeats();
                $tableRemaining = $activeTable->getRemainingSeats();
                $capacityPercent = $activeTable->capacity > 0 ? min(100, round(($tableOccupied / $activeTable->capacity) * 100)) : 0;
            @endphp

            <div class="tm-drawer-overlay" wire:click.self="closeTableDrawer">
                <div class="tm-drawer-panel">
                    {{-- Header Drawer --}}
                    <div class="tm-drawer-header">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <h3 class="tm-drawer-title">{{ $activeTable->name }}</h3>
                                @if($activeTable->zone)
                                    <span class="tm-drawer-zone-badge">
                                        {{ $activeTable->zone }}
                                    </span>
                                @endif
                            </div>
                            @if($activeTable->pic_name)
                                <p class="tm-drawer-subtitle">PIC: {{ $activeTable->pic_name }}</p>
                            @endif
                        </div>
                        <button type="button" wire:click="closeTableDrawer" class="tm-drawer-close" aria-label="Tutup panel">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Kapasitas & Progress Bar --}}
                    <div class="tm-drawer-capacity-box">
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; font-weight: 700;">
                            <span class="tm-guest-meta" style="margin-top: 0;">Kapasitas Kursi:</span>
                            <span class="tm-guest-name" style="font-size: 0.75rem;">
                                Terisi <span style="color: var(--brand-primary);">{{ $tableOccupied }}</span> / {{ $activeTable->capacity }}
                                (Sisa: <span style="color: {{ $tableRemaining > 0 ? '#10b981' : '#ef4444' }};">{{ $tableRemaining }}</span>)
                            </span>
                        </div>
                        <div class="tm-drawer-progress-track">
                            <div style="height: 100%; border-radius: 9999px; transition: width 0.3s ease; width: {{ $capacityPercent }}%; background-color: {{ $activeTable->getStatusColor() }};"></div>
                        </div>
                    </div>

                    {{-- Body Drawer: Form Assign & Daftar Tamu --}}
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1.25rem; flex: 1; overflow-y: auto;">
                        {{-- Form Cepat Plotting Tamu --}}
                        <div class="tm-drawer-assign-box">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h4 class="tm-drawer-section-title">
                                    Plot Tamu ke Meja Ini
                                </h4>
                                <span class="tm-drawer-subtitle" style="margin-top: 0; font-size: 0.6875rem;">{{ $unassignedGuests->count() }} Belum Punya Meja</span>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <select wire:model="selectedGuestToAssign" class="tm-select" style="width: 100%; font-size: 0.75rem;">
                                    <option value="">-- Pilih Tamu yang Belum Ditempatkan --</option>
                                    @foreach($unassignedGuests as $ug)
                                        @php
                                            $guestPax = ((int) $ug->adult_pax + (int) $ug->child_pax);
                                            if ($guestPax <= 0) $guestPax = 1;
                                        @endphp
                                        <option value="{{ $ug->id }}">
                                            {{ $ug->name }} ({{ $guestPax }} pax) - {{ $ug->tenant?->name ?? 'Tanpa Pengundang' }}
                                        </option>
                                    @endforeach
                                </select>

                                <button type="button"
                                        wire:click="assignGuest"
                                        class="tm-btn tm-btn-primary"
                                        style="width: 100%; padding-top: 0.5rem; padding-bottom: 0.5rem; justify-content: center;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>Tempatkan Tamu</span>
                                </button>
                            </div>
                        </div>

                        {{-- Daftar Tamu Meja Tersebut --}}
                        <div style="display: flex; flex-direction: column; gap: 0.625rem;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <h4 class="tm-drawer-section-title">
                                    Daftar Tamu di Meja ({{ $activeTable->guests->count() }})
                                </h4>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                @forelse($activeTable->guests as $tableGuest)
                                    @php
                                        $tgPax = ((int) $tableGuest->adult_pax + (int) $tableGuest->child_pax);
                                        if ($tgPax <= 0) $tgPax = 1;
                                    @endphp
                                    <div wire:key="table-guest-{{ $tableGuest->id }}" class="tm-guest-card">
                                        <div style="min-width: 0;">
                                            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.375rem;">
                                                <span class="tm-guest-name">{{ $tableGuest->name }}</span>
                                                <span class="tm-guest-pax">{{ $tgPax }} pax</span>
                                            </div>
                                            <div class="tm-guest-meta">
                                                <span>{{ $tableGuest->tenant?->name ?? 'Tanpa Pengundang' }}</span>
                                                <span>&bull;</span>
                                                @if($tableGuest->is_attended)
                                                    <span class="tm-status-attended">Hadir</span>
                                                @else
                                                    <span class="tm-status-pending">Belum Hadir</span>
                                                @endif
                                            </div>
                                        </div>

                                        <button type="button"
                                                wire:click="unassignGuest({{ $tableGuest->id }})"
                                                wire:confirm="Yakin ingin mengeluarkan {{ $tableGuest->name }} dari meja ini?"
                                                class="tm-unassign-btn"
                                                title="Keluarkan dari Meja">
                                            Keluarkan
                                        </button>
                                    </div>
                                @empty
                                    <div class="tm-drawer-empty">
                                        Belum ada tamu yang diplot ke meja ini.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        </div>
    </div>
</x-filament-panels::page>
