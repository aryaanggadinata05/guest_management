<?php

namespace App\Filament\Resources\TableResource\Pages;

use App\Filament\Resources\TableResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTable extends CreateRecord
{
    protected static string $resource = TableResource::class;

    protected function getRedirectUrl(): string
    {
        // Arahkan kembali ke halaman List (index) setelah berhasil Create
        return $this->getResource()::getUrl('index');
    }
}
