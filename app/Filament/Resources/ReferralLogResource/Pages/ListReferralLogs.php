<?php

namespace App\Filament\Resources\ReferralLogResource\Pages;

use App\Filament\Resources\ReferralLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferralLogs extends ListRecords
{
    protected static string $resource = ReferralLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
