<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('event_id')
                    ->label('Filter Event')
                    ->options(Event::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Semua Event Aktif'),

                Select::make('tenant_id')
                    ->label('Filter Tenant')
                    ->options(Tenant::pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Semua Tenant')
                    // Hanya Admin yang bisa melihat filter Tenant ini
                    ->visible(fn () => auth()->user()->role === 'admin'),
            ])
            ->columns(2);
    }
}
