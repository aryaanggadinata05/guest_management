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

        /* Viewport & Canvas */
        .tm-viewport {
            position: relative;
            overflow: auto;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            background-color: #0f172a;
            min-height: 540px;
            max-height: 75vh;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .dark .tm-viewport {
            border-color: #1e293b;
            background-color: #050811;
        }

        .tm-zoom-container {
            display: inline-block;
            transform-origin: 0 0;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tm-inner-canvas {
            position: relative;
            display: inline-block;
            line-height: 0;
            user-select: none;
            cursor: default;
        }
        .tm-inner-canvas.is-placing {
            cursor: crosshair !important;
        }

        /* Table Pins */
        .tm-pin {
            position: absolute;
            transform: translate(-50%, -50%);
            width: 44px;
            height: 44px;
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
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.2s ease;
            z-index: 10;
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

        /* Slide-over Drawer */
        .tm-drawer-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
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
    </style>

    <div x-data="{
        mode: @entangle('mode'),
        zoomLevel: @entangle('zoomLevel'),
        selectedTableForPosition: @entangle('selectedTableForPosition'),
        dragTargetId: null,
        startX: 0,
        startY: 0,
        isDragging: false,
        hasMovedPastThreshold: false,

        startDrag(tableId, event) {
            if (this.mode !== 'position') return;
            this.dragTargetId = tableId;
            this.startX = event.clientX || (event.touches ? event.touches[0].clientX : 0);
            this.startY = event.clientY || (event.touches ? event.touches[0].clientY : 0);
            this.hasMovedPastThreshold = false;
            this.isDragging = false;
        },

        onWindowMouseMove(event) {
            if (!this.dragTargetId) return;
            const clientX = event.clientX || (event.touches ? event.touches[0].clientX : 0);
            const clientY = event.clientY || (event.touches ? event.touches[0].clientY : 0);
            const distance = Math.hypot(clientX - this.startX, clientY - this.startY);
            if (distance > 4) {
                this.hasMovedPastThreshold = true;
                this.isDragging = true;
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

            this.dragTargetId = null;
            setTimeout(() => {
                this.isDragging = false;
                this.hasMovedPastThreshold = false;
            }, 60);
        },

        handlePinClick(tableId) {
            if (this.isDragging || this.hasMovedPastThreshold) return;

            if (this.mode === 'plotting') {
                $wire.openTableDrawer(tableId);
            } else {
                this.selectedTableForPosition = tableId;
                $wire.selectTableForPosition(tableId);
            }
        },

        // Detail 2: Presisi Klik Saat Zoom dihitung langsung dari inner canvas wrapper ($refs.canvas)
        handleCanvasClick(event) {
            if (this.isDragging || this.hasMovedPastThreshold) return;
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
        }
    }"
    @mousemove.window="onWindowMouseMove($event)"
    @mouseup.window="onWindowMouseUp($event)"
    @touchmove.window="onWindowMouseMove($event)"
    @touchend.window="onWindowMouseUp($event)"
    class="space-y-4">

        {{-- TOP CONTROL TOOLBAR --}}
        <div class="tm-card tm-toolbar">
            <div class="flex items-center flex-wrap gap-2.5">
                {{-- Event Selector --}}
                <div class="flex items-center gap-2">
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
                <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-900 p-1 border border-slate-200 dark:border-slate-800">
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
                <div class="inline-flex items-center rounded-xl bg-slate-100 dark:bg-slate-900 p-1 border border-slate-200 dark:border-slate-800 text-xs">
                    <button type="button" wire:click="setZoom(100)" class="tm-btn !border-0 !py-1 !px-2.5 {{ $zoomLevel === 100 ? 'tm-btn-active' : '' }}">100%</button>
                    <button type="button" wire:click="setZoom(125)" class="tm-btn !border-0 !py-1 !px-2.5 {{ $zoomLevel === 125 ? 'tm-btn-active' : '' }}">125%</button>
                    <button type="button" wire:click="setZoom(150)" class="tm-btn !border-0 !py-1 !px-2.5 {{ $zoomLevel === 150 ? 'tm-btn-active' : '' }}">150%</button>
                </div>

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

        {{-- CANVAS VIEWPORT AREA --}}
        <div class="tm-card overflow-hidden">
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
                {{-- Viewport dengan Kontrol Zoom --}}
                <div class="tm-viewport">
                    <div class="tm-zoom-container" :style="'transform: scale(' + (zoomLevel / 100) + ');'">
                        {{-- Detail 2: Inner Canvas Wrapper tempat @click listener dipasang --}}
                        <div class="tm-inner-canvas"
                             :class="{ 'is-placing': mode === 'position' && selectedTableForPosition }"
                             x-ref="canvas"
                             @click="handleCanvasClick($event)">
                            {{-- Gambar Denah Ruangan --}}
                            <img src="{{ $currentEvent->getFloorPlanUrl() }}"
                                 alt="Denah {{ $currentEvent->name }}"
                                 class="block max-w-full h-auto pointer-events-none select-none" />

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
                                     class="tm-pin {{ $isDimmed ? 'is-dimmed' : '' }} {{ $isHighlightedByTenant ? 'is-highlighted' : '' }}"
                                     :class="{ 'is-selected': selectedTableForPosition == {{ $table->id }} || {{ $activeTableId == $table->id ? 'true' : 'false' }} }"
                                     style="left: {{ $table->pos_x }}%; top: {{ $table->pos_y }}%; background-color: {{ $statusColor }};"
                                     @mousedown="startDrag({{ $table->id }}, $event)"
                                     @touchstart="startDrag({{ $table->id }}, $event)"
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
                                <span class="text-xs font-black text-slate-800 dark:text-slate-100">{{ $unmappedTable->name }}</span>
                                <span class="text-[10px] text-slate-500">{{ $unmappedTable->capacity }} Kursi</span>
                                @if($unmappedTable->zone)
                                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-semibold mt-0.5">
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
                    <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between bg-slate-50 dark:bg-slate-900/60">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slate-900 dark:text-slate-100">{{ $activeTable->name }}</h3>
                                @if($activeTable->zone)
                                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                        {{ $activeTable->zone }}
                                    </span>
                                @endif
                            </div>
                            @if($activeTable->pic_name)
                                <p class="text-xs text-slate-500 dark:text-slate-400">PIC: {{ $activeTable->pic_name }}</p>
                            @endif
                        </div>
                        <button type="button" wire:click="closeTableDrawer" class="p-1.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Kapasitas & Progress Bar --}}
                    <div class="p-4 border-b border-slate-200 dark:border-slate-700 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-600 dark:text-slate-400">Kapasitas Kursi:</span>
                            <span class="text-slate-900 dark:text-slate-100">
                                Terisi <span class="text-blue-600 dark:text-blue-400">{{ $tableOccupied }}</span> / {{ $activeTable->capacity }}
                                (Sisa: <span class="{{ $tableRemaining > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $tableRemaining }}</span>)
                            </span>
                        </div>
                        <div class="w-full h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300"
                                 style="width: {{ $capacityPercent }}%; background-color: {{ $activeTable->getStatusColor() }};"></div>
                        </div>
                    </div>

                    {{-- Body Drawer: Form Assign & Daftar Tamu --}}
                    <div class="p-4 space-y-5 flex-1 overflow-y-auto">
                        {{-- Form Cepat Plotting Tamu --}}
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Plot Tamu ke Meja Ini
                                </h4>
                                <span class="text-[10px] text-slate-400">{{ $unassignedGuests->count() }} Belum Punya Meja</span>
                            </div>

                            <div class="space-y-2">
                                <select wire:model="selectedGuestToAssign" class="tm-select w-full text-xs">
                                    <option value="">-- Pilih Tamu yang Belum Ditempatkan --</option>
                                    @foreach($unassignedGuests as $ug)
                                        @php
                                            $guestPax = ((int) $ug->adult_pax + (int) $ug->child_pax);
                                            if ($guestPax <= 0) $guestPax = 1;
                                        @endphp
                                        <option value="{{ $ug->id }}">
                                            {{ $ug->name }} ({{ $guestPax }} pax) - {{ $ug->tenant->name ?? 'No Tenant' }}
                                        </option>
                                    @endforeach
                                </select>

                                <button type="button"
                                        wire:click="assignGuest"
                                        class="tm-btn tm-btn-primary w-full !py-2 justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>Tempatkan Tamu</span>
                                </button>
                            </div>
                        </div>

                        {{-- Daftar Tamu Meja Tersebut --}}
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Daftar Tamu di Meja ({{ $activeTable->guests->count() }})
                                </h4>
                            </div>

                            <div class="space-y-2">
                                @forelse($activeTable->guests as $tableGuest)
                                    @php
                                        $tgPax = ((int) $tableGuest->adult_pax + (int) $tableGuest->child_pax);
                                        if ($tgPax <= 0) $tgPax = 1;
                                    @endphp
                                    <div wire:key="table-guest-{{ $tableGuest->id }}"
                                         class="p-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2 shadow-sm">
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $tableGuest->name }}</span>
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                                    {{ $tgPax }} pax
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-2">
                                                <span>{{ $tableGuest->tenant->name ?? '-' }}</span>
                                                <span>•</span>
                                                @if($tableGuest->is_attended)
                                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Hadir</span>
                                                @else
                                                    <span class="text-slate-400">Belum Hadir</span>
                                                @endif
                                            </div>
                                        </div>

                                        <button type="button"
                                                wire:click="unassignGuest({{ $tableGuest->id }})"
                                                wire:confirm="Yakin ingin mengeluarkan {{ $tableGuest->name }} dari meja ini?"
                                                class="text-xs font-semibold px-2 py-1 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 transition"
                                                title="Keluarkan dari Meja">
                                            Keluarkan
                                        </button>
                                    </div>
                                @empty
                                    <div class="p-6 text-center text-xs text-slate-400 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl">
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
</x-filament-panels::page>
