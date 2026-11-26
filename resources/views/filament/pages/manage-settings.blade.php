<x-filament-panels::page>
    <style>
        .aaa-sysinfo-card {
            margin-top: 1.5rem;
            padding: 1rem 1.25rem;
            border-radius: 0.875rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.8125rem;
            color: #475569;
        }
        .dark .aaa-sysinfo-card {
            background-color: #0f172a;
            border-color: #1e293b;
            color: #94a3b8;
        }
        .aaa-sysinfo-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dark .aaa-sysinfo-title {
            color: #e2e8f0;
        }
        .aaa-sysinfo-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            background-color: #e2e8f0;
            color: #1e293b;
        }
        .dark .aaa-sysinfo-badge {
            background-color: #1e293b;
            color: #e2e8f0;
        }
    </style>

    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
    </x-filament-panels::form>

    <div class="aaa-sysinfo-card" aria-label="Informasi Sistem">
        <div style="display: flex; flex-direction: column; gap: 0.2rem;">
            <span class="aaa-sysinfo-title">Informasi Sistem</span>
            <span>Application: <strong>Guest Management System v1.0</strong></span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <span>Copyright &amp; Ownership:</span>
            <span class="aaa-sysinfo-badge">&copy; 2026 AAA-05 — Proprietary Software</span>
        </div>
    </div>
</x-filament-panels::page>
